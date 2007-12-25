#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Long;
use Pod::Usage;
use Time::Local;

use vars qw (
             %prefs
             $dbh
             $dbh_update
             $debug
             $debug_time
             $opt_db
             $opt_debug
             $opt_help
             $opt_manual
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

# -------------
# Main routine
# -------------

print ">>> ring-set-new.pl                    v:30-Oct-2006\n";

# -- get options
GetOptions(
           'db=s'           => \$opt_db,
           'debug'          => \$opt_debug,
           'help'           => \$opt_help,
           'host=s'         => \$opt_host,
           'manual'         => \$opt_manual,
           'pass=s'         => \$opt_pass,
           'user=s'         => \$opt_user
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

my $sel = "SELECT i.pid pid ";
$sel .= "FROM pictures_information i ";
$sel .= "LEFT OUTER JOIN picture_details d ";
$sel .= "ON (i.pid = d.pid) ";
$sel .= "WHERE d.pid IS NULL ";
my $sth = $dbh->prepare ($sel);
if ($opt_debug) {debug_output($sel);}
$sth->execute();

my @pidList;
while (my $row = $sth->fetchrow_hashref) {
    push @pidList, $row->{pid};
}

my $cmd = "INSERT INTO picture_details ";
$cmd .= "(uid, pid) VALUES (?, ?) ";
if ($opt_debug) {debug_output($cmd);}
my $sth_update = $dbh_update->prepare ($cmd);

foreach my $i (@pidList) {
    $sth_update->execute('new',$i);
}

$dbh->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (read)";
$dbh_update->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (update)";

exit;

__END__

=head1 NAME

ring-set-net.pl

=head1 SYNOPSIS

 ring-restructure.pl \
              --host=mysql-host --db=databasename \
              --user=mysql-username --pass=mysql-password 
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Set a UID of new for any picture that does not have at least one UID.

=head1 OPTIONS AND ARGUMENTS

=over 4

=item --host=mysql-hostname

MySQL host name.  Value from ~/.rings will be used if available.

=item --db=databasename

The name of the MySQL database.  Value from ~/.rings will be used if
available.

=item --user=mysql-username

MySQL username.  Value from ~/.rings will be used if available.

=item --host=mysql-password

MySQL password.  Value from ~/.rings will be used if available.

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

