#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Long;
use Image::Magick;
use Pod::Usage;
use Time::Local;

use vars qw (
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
# process the files

sub read_and_update {
    
    # -- get a list of pid's first
    my $cnt = 0;

    my $sel = "SELECT ";
    $sel .= "pid ";
    $sel .= "FROM pictures ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();

    my @pidList;
    while (my $row = $sth->fetchrow_hashref) {
        $cnt++;
        push @pidList, $row->{pid};
    }

    my $cmd = "UPDATE pictures_information SET ";
    $cmd .= "raw_picture_size = ? ";
    $cmd .= "WHERE pid = ? ";
    if ($opt_debug) {debug_output($cmd);}
    my $sth_update = $dbh_update->prepare ($cmd);

    foreach my $i (@pidList) {

        my $sel = "SELECT ";
        $sel .= "picture ";
        $sel .= "FROM pictures_raw ";
        $sel .= "WHERE pid=$i ";
        my $sth = $dbh->prepare ($sel);
        if ($opt_debug) {debug_output($sel);}
        $sth->execute();
        if (my $row = $sth->fetchrow_hashref) {
            my $l = length($row->{picture});
            if ($l > 0) {
                debug_output("pid:$i length:$l");
                if ($opt_update) {
                    $sth_update->execute(
                                         $l,
                                         $i);
                }
            }
        }

    }

}

# -------------
# Main routine
# -------------

print ">>> ring-fix.pl                    v:10-Jul-2006\n";

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

Bill MacAllister <bill.macallister@prideindustries.com>

=cut

