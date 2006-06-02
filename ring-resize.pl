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
# process the files

sub read_and_update {
    
    my $sel = "SELECT pid,file_name,picture_type,picture FROM pictures ";
    $sel .= "WHERE pid >= $opt_start ";
    if ($opt_end > $opt_start) {
        $sel .= "AND pid <= $opt_end ";
    }
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();
    
    my @blob;
    
    while (my $row = $sth->fetchrow_hashref) {
        $cnt++;
        
        debug_output ("Processing $row->{file_name} $row->{picture_type} ($row->{pid})");
        
        $blob[0] = $row->{picture};
        my $thisPic = Image::Magick->New();
        $thisPic->BlobToImage(@blob);
        
        my ($width, $height) = $thisPic->Get('base-width','base-height');
        
        if ($width==0 || $height==0) {
            debug_output ("      width: $width");
            debug_output ("     height: $height");
            debug_output (" Skipping image");
            next;
        }
        if ($opt_debug) {
            debug_output ("      width: $width");
            debug_output ("     height: $height");
        }
        
        my $newSVGA   = $thisPic->Clone();
        my $newVGA    = $thisPic->Clone();
        my $newThumb  = $thisPic->Clone();
        
        # -- A SVGA sized picture
        
        my $max_x = 800;
        my $max_y = 600;
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
        debug_output (" Producing svga picture");
        $newSVGA->Resize(width=>$x, height=>$y);
        
        # -- A VGA sized picture
        
        my $max_x = 600;
        my $max_y = 480;
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
        debug_output (" Producing vga picture");
        $newVGA->Resize(width=>$x, height=>$y);
        
        # -- Make the thumbnail 
        
        my $max_x = 125;
        my $max_y = 125;
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
        debug_output (" Producing thumbnail");
        $newThumb->Resize(width=>$x, height=>$y);
        
        my @bPic    = $thisPic->ImageToBlob();
        my @bSVGA   = $newSVGA->ImageToBlob();
        my @bVGA    = $newVGA->ImageToBlob();
        my @bThumb  = $newThumb->ImageToBlob();
        
        # -- update the picture record
        
        my $cmd = "UPDATE pictures ";
        $cmd .= "SET date_last_maint='".sql_datetime()."', ";
        $cmd .= "picture_large  = ".$dbh->quote($bVGA[0]).", ";
        $cmd .= "picture_larger = ".$dbh->quote($bSVGA[0]).", ";
        $cmd .= "picture_small  = ".$dbh->quote($bThumb[0])." ";
        $cmd .= "WHERE pid = $row->{pid} ";
        if ($opt_debug) {debug_output("length of sql command: ".length($cmd));}
        if ($opt_update) {
            my $sth_update = $dbh_update->prepare ($cmd);
            $sth_update->execute();
            debug_output(" Update of $row->{pid} complete.");
        } else {
            debug_output(" Proposing SQL command ".length($cmd)." bytes long.");
        }
        
        # -- clean up
        undef $thisPic;
        undef $newVGA;
        undef $newSVGA;
        undef $newThumb;
    }
}

# -------------
# Main routine
# -------------

print ">>> ring-resize.pl                    v: 8-May-2005\n";

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

