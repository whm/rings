#!/usr/bin/perl

use strict;
use Cwd;
use DBI;
use Getopt::Long;
use Image::Magick;
use Pod::Usage;
use Time::Local;

use vars qw (
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
# store pictures

sub store_picture {

    my ($thisPID, 
        $thisTable, 
        $thisPicture,
        $thisType) = @_;
    
    debug_output ("Processing $thisPID $thisTable");
    
    my @blob;
    $blob[0] = $thisPicture;
    my $thisPic = Image::Magick->New();
    $thisPic->BlobToImage(@blob);
    
    my ($width, $height) = $thisPic->Get('base-width','base-height');
    
    if ($width==0 || $height==0) {
        debug_output ("      width: $width");
        debug_output ("     height: $height");
        debug_output (" Skipping image");
        return;
    }

    # default to moderate
    my $max_x = 640;
    my $max_y = 480;

    if ($thisTable eq 'pictures_large') {
        $max_x = 640;
        $max_y = 480;
    } elsif ($thisTable eq 'pictures_larger') {
        $max_x = 800;
        $max_y = 600;
    } elsif ($thisTable eq 'pictures_small') {
        $max_x = 125;
        $max_y = 125;
    }
    
    my $newPic   = $thisPic->Clone();
    my $x = $width;
    my $y = $height;
    my $x1 = $max_x;
    my $y1 = ($x1/$width) * $height;
    my $y2 = $max_y;
    my $x2 = ($y2/$height) * $width;
    if ($x1 < $x2) {
        $x = $x1;
        $y = $y1;
    } else {
        $x = $x2;
        $y = $y2;
    }
    debug_output (" Producing picture $x by $y ");
    $newPic->Resize(width=>$x, height=>$y);
    my @bPic  = $newPic->ImageToBlob();
    
    my $sel = "SELECT pid FROM $thisTable ";
    $sel .= "WHERE pid=$thisPID ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();
    
    my $row = $sth->fetchrow_hashref;

    if ($row->{pid} != $thisPID && $thisPID > 0) {
        
        my $cmd = "INSERT INTO $thisTable (";
        $cmd .= 'pid,';
        $cmd .= 'picture_type,';
        $cmd .= 'width,';
        $cmd .= 'height,';
        $cmd .= 'picture,';
        $cmd .= 'date_last_maint,';
        $cmd .= 'date_added';
        $cmd .= ') VALUES (?,?,?,?,?,?,?) ';
        my $sth_update = $dbh_update->prepare ($cmd);
        if ($opt_debug) {debug_output($cmd);}
        if ($opt_update) {
            $sth_update->execute(
                                 $thisPID,
                                 $thisType,
                                 $width,
                                 $height,
                                 $bPic[0],
                                 sql_datetime(),
                                 sql_datetime()
                                 );
        }
        
    } elsif ($thisPID > 0) {
        
        my $cmd = "UPDATE $thisTable SET ";
        $cmd .= 'picture_type = ?,';
        $cmd .= 'width = ?,';
        $cmd .= 'height = ?,';
        $cmd .= 'picture = ?,';
        $cmd .= 'date_last_maint = ? ';
        $cmd .= 'WHERE pid = ? ';
        my $sth_update = $dbh_update->prepare ($cmd);
        if ($opt_debug) {debug_output($cmd);}
        if ($opt_update) {
            $sth_update->execute(
                                 $thisType,
                                 $width,
                                 $height,
                                 $bPic[0],
                                 sql_datetime(),
                                 $thisPID,
                                 );
        }
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
    if ($opt_end > $opt_start) {
        $sel .= "AND pid <= $opt_end ";
    }
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();
    while (my $row = $sth->fetchrow_hashref) {
        $cnt++;
        $pidList{$row->{pid}} = $row->{file_name};
    }
    debug_output ("$cnt pictures to process");
    
    # process the pictures

    foreach my $i (keys %pidList) {
        debug_output ("Processing $pidList{$i}...");
        
        my $sel = "SELECT ";
        $sel .= "picture_type,";
        $sel .= "picture ";
        $sel .= "FROM pictures_raw ";
        $sel .= "WHERE pid = $i ";
        my $sth = $dbh->prepare ($sel);
        if ($opt_debug) {debug_output($sel);}
        $sth->execute();
        
        if (my $row = $sth->fetchrow_hashref) {
            
            store_picture ($i, 
                           'pictures_small', 
                           $row->{picture}, 
                           $row->{picture_type});
            store_picture ($i, 
                           'pictures_large', 
                           $row->{picture}, 
                           $row->{picture_type});
            store_picture ($i, 
                           'pictures_larger', 
                           $row->{picture}, 
                           $row->{picture_type});
            
        }
    }
}
    
# -------------
# Main routine
# -------------

print ">>> ring-resize.pl                    v:11-Jul-2006\n";

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

ring-resize.pl

=head1 SYNOPSIS

 ring-resize.pl --start=int [--end=int] [--update] \
              [--host=mysql-host] [--db=databasename] \
              --user=mysql-username --pass=mysql-password 
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Resize pictures in the rings database.

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

Bill MacAllister <bill.macallister@prideindustries.com>

=cut

