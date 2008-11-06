#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Long;
use Image::Magick;
use Pod::Usage;
use Time::Local;

use vars qw (
             %pidList
             $dbh
             $dbh_update
             $debug
             $debug_time
             $opt_db
             $opt_debug
             $opt_help
             $opt_manual
             $opt_update
             $opt_host
             $opt_user
             $opt_pass
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
# clean out target table

sub empty_table {

    my ($t) = @_;         

    # -- clean out the target tables
    my $cmd = "TRUNCATE TABLE $t";
    if ($opt_debug) {debug_output($cmd);}
    my $sth_update = $dbh_update->prepare ($cmd);
    $sth_update->execute();

}

# ------------------------------------------------
# store pictures

sub store_picture {

    my ($thisPID, 
        $thisTable, 
        $thisField) = @_;

    debug_output ("Processing $thisPID $thisField");

    my $sel = "SELECT $thisField pict FROM pictures ";
    $sel .= "WHERE pid=$thisPID ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();
        
    if (my $row = $sth->fetchrow_hashref) {
            
        debug_output ();

        my @blob;

        $blob[0] = $row->{pict};
        my $thisPic = Image::Magick->New();
        $thisPic->BlobToImage(@blob);
        
        my ($width, $height) = $thisPic->Get('base-width','base-height');
        
        debug_output ("Size:".length($row->{pict}) . 
                      " Image geometry: $width by $height");

        my $cmd = "INSERT INTO $thisTable (";
        $cmd .= 'pid,';
        $cmd .= 'picture_type,';
        $cmd .= 'width,';
        $cmd .= 'height,picture,';
        $cmd .= 'date_last_maint,';
        $cmd .= 'date_added';
        $cmd .= ') VALUES (?,?,?,?,?,?,?) ';
        my $sth_update = $dbh_update->prepare ($cmd);
        if ($opt_debug) {debug_output($cmd);}
        if ($opt_update) {
            $sth_update->execute(
                                 $thisPID,
                                 $pidList{$thisPID}{'type'},
                                 $width,
                                 $height,
                                 $row->{pict},
                                 $pidList{$thisPID}{'last'},
                                 $pidList{$thisPID}{'add'}
                                 );
        }

    }

}

# ------------------------------------------------
# process the files

sub read_and_update {
    
    # -- clean out the target tables
    if ($opt_update) {
        empty_table ('pictures_information');
        empty_table ('pictures_raw');
        empty_table ('pictures_small');
        empty_table ('pictures_large');
        empty_table ('pictures_larger');
    }

    # -- get a list of pid's first
    my $cnt = 0;

    my $sel = "SELECT ";
    $sel .= "pid, ";
    $sel .= "picture_type, ";
    $sel .= "key_words, ";
    $sel .= "file_name, ";
    $sel .= "date_taken, ";
    $sel .= "taken_by, ";
    $sel .= "description, ";
    $sel .= "grade, ";
    $sel .= "public, ";
    $sel .= "date_last_maint, "; 
    $sel .= "date_added ";
    $sel .= "FROM pictures ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();

    my $cmd = "INSERT INTO pictures_information (";
    $cmd .= "pid,";
    $cmd .= "key_words,";
    $cmd .= "file_name,";
    $cmd .= "date_taken,";
    $cmd .= "taken_by,";
    $cmd .= "description,";
    $cmd .= "grade,";
    $cmd .= "public,";
    $cmd .= "date_last_maint,";
    $cmd .= "date_added ";
    $cmd .= ") VALUES (";
    $cmd .= "?,?,?,?,?,?,?,?,?,?) ";
    if ($opt_debug) {debug_output($cmd);}
    my $sth_update = $dbh_update->prepare ($cmd);

    while (my $row = $sth->fetchrow_hashref) {
        $cnt++;
        $pidList{$row->{pid}}{'type'} = $row->{picture_type};
        $pidList{$row->{pid}}{'last'} = $row->{date_last_maint};
        $pidList{$row->{pid}}{'add'} = $row->{date_added};
        if ($opt_update) {
            $sth_update->execute(
                                 $row->{pid},
                                 $row->{key_words},
                                 $row->{file_name},
                                 $row->{date_taken},
                                 $row->{taken_by},
                                 $row->{description},
                                 $row->{grade},
                                 $row->{public},
                                 $row->{date_last_maint},
                                 $row->{date_added}
                                 );
        }

    }
    debug_output ("$cnt pictures to process");

    foreach my $i (keys %pidList) {

        store_picture($i, 'pictures_raw', 'picture');
        store_picture($i, 'pictures_small', 'picture_small');
        store_picture($i, 'pictures_large', 'picture_large');
        store_picture($i, 'pictures_larger', 'picture_larger');

    }

}

# -------------
# Main routine
# -------------

print ">>> ring-restructure.pl                    v:10-Jul-2006\n";

# -- get options
GetOptions(
           'db=s'           => \$opt_db,
           'debug'          => \$opt_debug,
           'help'           => \$opt_help,
           'host=s'         => \$opt_host,
           'manual'         => \$opt_manual,
           'pass=s'         => \$opt_pass,
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
    print "%MAC-F-HOSTREQ, a MySQL host is required\n";
    pod2usage(-verbose => 0);
    exit;
}

if (length($opt_db) == 0) {
    print "%MAC-F-DBREQ, a MySQL database is required\n";
    pod2usage(-verbose => 0);
    exit;
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

ring-restructure.pl

=head1 SYNOPSIS

 ring-restructure.pl [--update] \
              --host=mysql-host --db=databasename \
              --user=mysql-username --pass=mysql-password 
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Move picture data around a bit.

=head1 OPTIONS AND ARGUMENTS

=over 4

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

