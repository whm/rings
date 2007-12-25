#!/usr/bin/perl

use strict;
use DBI;
use Getopt::Long;
use Image::Magick;
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
             $opt_update
             $opt_host
             $opt_start
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

# -------------
# Main routine
# -------------

print ">>> ring-fix-details.pl                    v:10-Jul-2006\n";

# -- get options
GetOptions(
           'db=s'           => \$opt_db,
           'debug'          => \$opt_debug,
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

if ($opt_debug) {debug_output ("Initialize timer.");}

# -- Open up connections to the MySQL data

my $dbi = "dbi:mysql:host=$opt_host;database=$opt_db";
$dbh = DBI->connect ($dbi, $opt_user, $opt_pass)
    or die "%MAC-F-CANTCONN, Can't connect to database $dbi for read\n";
$dbh->{LongTruncOk} = 1;
$dbh->{LongReadLen} = 10000000;
$dbh_update = DBI->connect ($dbi, $opt_user, $opt_pass)
    or die "%MAC-F-CANTCONN, Can't connect to database $dbi for update\n";

my $sel = "SELECT d.pid ";
$sel .= "FROM picture_details d ";
$sel .= "LEFT OUTER JOIN pictures_information i ";
$sel .= "ON (i.pid = d.pid) ";
$sel .= "WHERE i.pid IS NULL ";
my $sth = $dbh->prepare ($sel);
if ($opt_debug) {debug_output($sel);}
$sth->execute();
my @pidList;
while (my $row = $sth->fetchrow_hashref) {
    push @pidList, $row->{pid};
}

my $cmd = "DELETE FROM picture_details ";
$cmd .= "WHERE pid = ? ";
if ($opt_debug) {debug_output($cmd);}
my $sth_update = $dbh_update->prepare ($cmd);
foreach my $i (@pidList) {
    if ($opt_update) {
        $sth_update->execute($i);
    } else {
        print "Proposing to delete pictures_details for $i\n";
    }
}

$dbh->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (read)";
$dbh_update->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (update)";

exit;

__END__

=head1 NAME

ring-fix-details.pl

=head1 SYNOPSIS

 ring-restructure.pl [--update] \
              --host=mysql-host --db=databasename \
              --user=mysql-username --pass=mysql-password 
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Delete picture details that point to pictures that have been deleted.

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

