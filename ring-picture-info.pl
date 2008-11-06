#!/usr/bin/perl

use strict;
use Cwd;
use DBI;
use Getopt::Long;
use Image::ExifTool 'ImageInfo';
use Pod::Usage;
use Time::Local;

use vars qw (
             %prefs
             $cnt
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

sub debug_output {
    
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
# process the files

sub read_and_update {
    
    # get a list to process first because we need to process
    # the potentially large blob one at a time

    my @pid_list;

    my $sel = "SELECT pid ";
    $sel .= "FROM pictures_information ";
    $sel .= "WHERE pid >= $opt_start ";
    if ($opt_end > 0) {
        $sel .= "AND pid <= $opt_end ";
    }
    $sel .= "ORDER BY pid ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();
    while (my $row = $sth->fetchrow_hashref) {
        push @pid_list, $row->{pid};
    }

    my $pic_cnt = scalar(@pid_list);
    if ($pic_cnt < 1) {
        print "\n";
        print "No pictures found to update\n";
        return;
    } else {
        print "\n";
        print "$pic_cnt pictures found to examine for updates\n";
        print "\n";
    }

    # now get the pictures one at a time
    foreach my $thisPID (@pid_list) {

        my $sel = "SELECT p.pid,";
        $sel .= "p.camera, ";
        $sel .= "p.date_taken, ";
        $sel .= "p.picture_date, ";
        $sel .= "p.fstop, ";
        $sel .= "p.raw_picture_size, ";
        $sel .= "p.shutter_speed, ";
        $sel .= "p.date_added, ";
        $sel .= "r.picture ";
        $sel .= "FROM pictures_information p ";
        $sel .= "JOIN pictures_raw r ";
        $sel .= "ON (p.pid = r.pid) ";
        $sel .= "WHERE p.pid = $thisPID ";
        my $sth = $dbh->prepare ($sel);
        if ($opt_debug) {debug_output($sel);}
        $sth->execute();
        while (my $row = $sth->fetchrow_hashref) {

            my $thisImage = $row->{picture};

            my $info = ImageInfo(\$thisImage);

            foreach my $t (keys %{$info}) {
                print "$t = ${$info}{$t}\n";
            }

#            my $cmd = 'UPDATE pictures_information SET ';
#            $cmd .= 'camera=?,';
#            $cmd .= 'picture_date=?,';
#            $cmd .= 'fstop=?,';
#            $cmd .= 'raw_picture_size=?,';
#            $cmd .= 'shutter_speed=?,';
#            $cmd .= 'date_last_maint=? ';
#            $cmd .= 'WHERE pid=? ';
#            my $sth_update = $dbh_update->prepare ($cmd);
#            if ($opt_debug) {debug_output($cmd);}
#            if ($opt_update) {
#                print "Updating $thisPID, date taken $this_datetime\n";
#                $sth_update->execute($camera,
#                                     $this_datetime,
#                                     $this_fnumber,
#                                     $this_pic_size,
#                                     $this_shutterspeed,
#                                     sql_datetime(),
#                                         $thisPID
#                                     );
#            } else {
#                print "For pid $thisPID proposing to set:\n";
#                print "    Camera: $camera\n";
#                print "    Picture Date: $this_datetime\n";
#                print "    FStop: $this_fnumber\n";
#                print "    Picture Size:$this_pic_size\n";
#                print "    Shutter Speed: $this_shutterspeed\n";
#            }

        }
    }
}

# -------------
# Main routine
# -------------

print ">>> ring-set-info.pl                    v:11-Jul-2006\n";

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
my $pref_file = $ENV{'HOME'}.'/.rings';
if ( -e $pref_file) {
    if ($opt_debug) {debug_output("Reading $pref_file file");}
    open (pref, "<$pref_file");
    while (<pref>) {
        chomp;
        my $inline = $_;
        $inline =~ s/\#.*//;
        if ($opt_debug) {debug_output("inline:$inline");}
        if (length($inline) > 0) {
            if ($inline =~ /^\s*(host|db|user|pass)=(.*)/i) {
                my $attr = lc($1);
                my $val = $2;
                $val =~ s/\s+$//;
                $prefs{$attr} = $val;
                if ($opt_debug) {debug_output("attr:$attr val:$val");}
            }
        }
    }
    close pref;
}

if (length($opt_host) == 0)    {$opt_host = $prefs{'host'};}
if (length($opt_db) == 0)      {$opt_db = $prefs{'db'};}
if (length($opt_pass) == 0)    {$opt_pass = $prefs{'pass'};}
if (length($opt_user) == 0)    {$opt_user = $prefs{'user'};}

if (length($opt_host) == 0) {
    $opt_host = 'localhost';
}
if (length($opt_db) == 0) {
    $opt_db = 'rings';
}

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

if ($opt_debug) {debug_output ("Initialize timer.");}

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

ring-set-info.pl

=head1 SYNOPSIS

 ring-set-info.pl --start=int [--end=int] [--update] \
              [--host=mysql-host] [--db=databasename] \
              --user=mysql-username --pass=mysql-password 
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Set information from the raw picture.  This works for some digital cameras.

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

