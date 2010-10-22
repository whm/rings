#!/usr/bin/perl

use strict;
use Cwd;
use DBI;
use Getopt::Long;
use Image::Magick;
use Pod::Usage;
use Time::Local;

use vars qw (
             %prefs
             %tableList
             $dbh
             $dbh_update
             $debug
             $debug_time
             $opt_db
             $opt_debug
             $opt_end
             $opt_host
             $opt_help
             $opt_manual
             $opt_pass
             $opt_start
             $opt_user
             $opt_update
             );

# ------------------------------------------------
# output debugging information

sub dbg {
    
    (my $tmp) = @_;
    
    my $now = time;
    my $elapsed = $now - $debug_time;
    print "$now ($elapsed) $tmp \n";
    $debug_time = $now;
    return;
    
}

# ------------------------------------------------
# sql date time string from unix time stamp

sub sql_datetime {
    
    my ($dt) = @_;
    
    if (length($dt)==0) {$dt = time}
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($dt);
    $mon++;
    $year += 1900;
    
    return sprintf "%04d-%02d-%02d %02d:%02d:%02d",
    $year,$mon,$mday,$hour,$min,$sec;
}

# ------------------------------------------------
# unix time stamp from sql date time string

sub unix_seconds {
    
    my ($dt) = @_;
    
    my $ret = time;
    if ($dt =~ m/(\d+)\-(\d+)\-(\d+)\s+(\d+):(\d+):(\d+)/ ) {
        my $yyyy = $1;
        my $mm = $2;
        my $dd = $3;
        my $h = $4;
        my $m = $5;
        my $s = $6;
        $mm--;
        $ret = timelocal ($s, $m, $h, $dd, $mm, $yyyy);
    }
    return $ret;
}

# ------------------------------------------------
# rotate a picture

sub rotate_picture {

    my ($thisPID, 
        $thisSeq,
        $thisPicture,
        $thisType) = @_;
    
    dbg (" Processing $thisPID");
    
    my @blob;
    $blob[0] = $thisPicture;
    my $thisPic = Image::Magick->New();
    $thisPic->BlobToImage(@blob);
    my ($width, 
        $height, 
        $size, 
        $format, 
        $compression,
        $camera,
        $this_datetime,
        $this_shutterspeed,
        $this_fnumber) 
        = $thisPic->Get('width',
                        'height',
                        'filesize',
                        'format',
                        'compression',
                        '%[EXIF:Model]',
                        '%[EXIF:DateTime]',
                        '%[EXIF:ExposureTime]',
                        '%[EXIF:FNumber]');
    
    # Rotate the image and create the blob to store
    $thisPic->Rotate(degrees => 90);
    my @bPic  = $thisPic->ImageToBlob();
    
    my $cmd = "UPDATE pictures_raw SET ";
    $cmd .= 'picture_type = ?,';
    $cmd .= 'width = ?,';
    $cmd .= 'height = ?,';
    $cmd .= 'picture = ?,';
    $cmd .= 'date_last_maint = ? ';
    $cmd .= 'WHERE pid = ? ';
    my $sth_update = $dbh_update->prepare ($cmd);
    if ($opt_debug) {dbg($cmd);}
    if ($opt_update) {
        $sth_update->execute(
                             $thisType,
                             $height,
                             $width,
                             $bPic[0],
                             sql_datetime(),
                             $thisPID,
                            );
    }
    
}

# ------------------------------------------------
# process the files

sub read_and_update {
    
    my %pidList;
    
    # get a list of pids first

    my $sel = "SELECT pid,";
    $sel .= "file_name ";
    $sel .= "FROM pictures_information ";
    $sel .= "WHERE pid >= $opt_start ";
    if ($opt_end >= $opt_start) {
        $sel .= "AND pid <= $opt_end ";
    }
    $sel .= "ORDER BY pid ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {dbg($sel);}
    $sth->execute();
    my $cnt = 0;
    while (my $row = $sth->fetchrow_hashref) {
        $cnt++;
        $pidList{$row->{pid}} = $row->{file_name};
    }
    dbg ("$cnt pictures to process");
    
    # process the pictures

    my $cnt = 0;
    foreach my $i (sort keys %pidList) {
        dbg ("Processing $pidList{$i}...");
        
        my $sel = "SELECT ";
        $sel .= "picture_type,";
        $sel .= "picture ";
        $sel .= "FROM pictures_raw ";
        $sel .= "WHERE pid = $i ";
        my $sth = $dbh->prepare ($sel);
        if ($opt_debug) {dbg($sel);}
        $sth->execute();
        
        if (my $row = $sth->fetchrow_hashref) {
            rotate_picture ($i,
                            $cnt,
                            $row->{picture}, 
                            $row->{picture_type});
        }
    }
}
    
# -------------
# Main routine
# -------------

print ">>> ring-rotate.pl    v:22-Oct-2010\n";

# -- get options
GetOptions(
           'db=s'           => \$opt_db,
           'debug'          => \$opt_debug,
           'end=i'          => \$opt_end,
           'help'           => \$opt_help,
           'host=s'         => \$opt_host,
           'manual'         => \$opt_manual,
           'pass=s'         => \$opt_pass,
           'start=i'        => \$opt_start,
           'user=s'         => \$opt_user,
           'update'         => \$opt_update
           );

# -- help the poor souls out
if ($opt_help) {
    pod2usage(-verbose => 0);
}
if ($opt_manual) {
    pod2usage(-verbose => 1);
}

# -- read preferences from ./rings
my $pref_file = $ARGV[0];

$pref_file = $ENV{'HOME'}.'/.rings'   unless $pref_file;
$pref_file = ''                       unless -e $pref_file;

$pref_file = '/etc/whm/rings_db.conf' unless $pref_file;
$pref_file = ''                       unless -e $pref_file;

if ( -e $pref_file) {
    if ($opt_debug) {dbg("Reading $pref_file file");}
    open (pref, "<$pref_file");
    while (<pref>) {
        chomp;
        my $inline = $_;
        $inline =~ s/\#.*//;
        if ($opt_debug) {dbg("inline:$inline");}
        if (length($inline) > 0) {
            if ($inline =~ /^\s*(host|db|user|pass)\s*=\s*(.*)/i) {
                my $attr = lc($1);
                my $val = $2;
                $val =~ s/\s+$//;
                $prefs{$attr} = $val;
                if ($opt_debug) {dbg("attr:$attr val:$val");}
            }
        }
    }
    close pref;
}

$opt_host = $prefs{'host'} unless $opt_host;
$opt_host = 'localhost'    unless $opt_host;

$opt_db = $prefs{'db'} unless $opt_db;
$opt_db = 'rings'      unless $opt_db;

$opt_pass = $prefs{'pass'} unless $opt_pass;
$opt_user = $prefs{'user'} unless $opt_user;

if (length($opt_start) == 0) {
    print "%MAC-F-STARTREQ, Starting number required.  Try 1.\n";
    pod2usage(-verbose => 0);
}

if (length($opt_pass) == 0) {
    print "%MAC-F-PASSREQ, a MySQL password is required\n";
    pod2usage(-verbose => 0);
    exit;
}
if (length($opt_user) == 0) {
    print "%MAC-F-USERREQ, a MySQL username is required\n";
    pod2usage(-verbose => 0);
    exit;
}

%tableList = ('pictures_small' => 1,
              'pictures_large' => 1,
              'pictures_larger' => 1,
              'pictures_1280_1024' =>1);

if ($opt_debug) {dbg ("Initialize timer.");}

# -- Open up connections to the MySQL data

my $dbi = "dbi:mysql:host=$opt_host;database=$opt_db";
$dbh = DBI->connect ($dbi, $opt_user, $opt_pass)
    or die "%MAC-F-CANTCONN, Can't connect to database $dbi for read\n";
$dbh->{LongTruncOk} = 1;
$dbh->{LongReadLen} = 10000000;
$dbh_update = DBI->connect ($dbi, $opt_user, $opt_pass)
    or die "%MAC-F-CANTCONN, Can't connect to database $dbi for update\n";

read_and_update();

$dbh->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (read)";
$dbh_update->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (update)";

exit;

__END__

=head1 NAME

ring-rotate.pl

=head1 SYNOPSIS

 ring-rotate.pl --start=int [--end=int] [--update] [--dateupdate] \
              [--host=mysql-host] [--db=databasename] \
              --user=mysql-username --pass=mysql-password 
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Rotate pictures in the rings database.

=head1 OPTIONS AND ARGUMENTS

=over 4

=item --start=int

The picture id to start at.  Required.

=item --send=int

The picture id to end at.

=item --host=mysql-hostname

MySQL host name.  If not specified then localhost is used.

=item --db=databasename

The name of the MySQL database.  If not specified then rings is used.

=item --user=mysql-username

MySQL username.  Required.

=item --host=mysql-password

MySQL password.  Required.

=item --update

Actually load the data into the rings database.

=item --dateupdate

Update the date taken and picture size in the data base if they
are available in the image.

=item --help

Displays help text.

=item --manual

Displays more complete help text.

=item --debug

Turns on debugging displays.

=back

=head1 AUTHOR

Bill MacAllister <bill@macallister.grass-valley.ca.us>

=cut

