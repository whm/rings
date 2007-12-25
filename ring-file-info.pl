#!/usr/bin/perl

use strict;
use Cwd;
use DBI;
use Getopt::Long;
use Image::ExifTool 'ImageInfo';
use Pod::Usage;
use Time::Local;

use vars qw (
             $debug
             $debug_time
             $opt_debug
             $opt_file
             $opt_help
             $opt_manual
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

print ">>> ring-file-info.pl                    v: 5-Sep-2007\n";

# -- get options
GetOptions(
           'debug'          => \$opt_debug,
           'file=s'         => \$opt_file,
           'help'           => \$opt_help,
           'manual'         => \$opt_manual
           );

# -- help the poor souls out
if ($opt_help) {
    pod2usage(-verbose => 0);
}
if ($opt_manual) {
    pod2usage(-verbose => 1);
}


my $info = ImageInfo($opt_file);
foreach my $t (keys %{$info}) {
    print "$t = ${$info}{$t}\n";
}

exit;

__END__

=head1 NAME

ring-file-info.pl

=head1 SYNOPSIS

 ring-file-info.pl --file=string [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Display EXIF information from a picture file.

=head1 OPTIONS AND ARGUMENTS

=over 4

=item --file=string

The picture file name.  Required.

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

