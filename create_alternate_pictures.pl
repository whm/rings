#!/usr/bin/perl

use strict;
use Cwd;
use File::Find;
use Getopt::Long;
use Image::Magick;
use Pod::Usage;

use vars qw (
	     $cnt
	     $debug
	     $debug_time
	     $opt_debug
	     $opt_help
	     $opt_manual
	     $opt_path
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

sub everyfile {

    my $a_file = $_;

    if ($a_file =~ /(.*?)\.jpg$/i) {

	my $a_file_quoted = $1;
	$a_file_quoted =~ s/ /\\ /g;
	$a_file_quoted =~ s/(\')/\\$1/g;
	$a_file_quoted =~ s/(\&)/\\$1/g;
	$a_file_quoted =~ s/(\()/\\$1/g;
	$a_file_quoted =~ s/(\))/\\$1/g;

	$cnt++;
	debug_output ("processing $a_file...");


	# -- read the image
	my $thisPic = new Image::Magick;
	$thisPic->Read($a_file);

	my ($width, $height, $size, $format) = $thisPic->Get('width',
							     'height',
							     'filesize',
							     'format');
	if ($opt_debug) {
	    debug_output (" width: $width");
	    debug_output ("height: $height");
	    debug_output ("  size: $size");
	    debug_output ("format: $format");
	}

	my $newSVGA  = $a_file_quoted . '-svga.'.$format;
	my $newVGA   = $a_file_quoted . '-vga.'.$format;
	my $newThumb = $a_file_quoted . '-thumb'.$format;

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
	$thisPic->Resize(width=>$x, height=>$y);

	# -- write it out
	$thisPic->Write(filename=>$newVGA);

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
	$thisPic->Resize(width=>$x, height=>$y);

	# -- write it out
	$thisPic->Write(filename=>$newVGA);

	# -- Make the thumbnail 

	my $max_x = 100;
	my $max_y = 100;
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
	$thisPic->Resize(width=>$x, height=>$y);

	# -- write it out
	$thisPic->Write(filename=>$newThumb);

    }
}

# -------------
# Main routine
# -------------

# -- get options
GetOptions(
           'debug'      => \$opt_debug,
           'help'       => \$opt_help,
           'manual'     => \$opt_manual,
           'path=s'     => \$opt_path
	   );

# -- help the poor souls out
if ($opt_help) {
    pod2usage(-verbose => 1);
}
if ($opt_manual) {
    pod2usage(-verbose => 2);
}

debug_output ("Initialize timer.");

my $thisDir = '.';
if (length($opt_path)>0 ) {
  $thisDir = $opt_path;
}

my $a_dir = cwd();

print "Examining files in $thisDir\n";

$cnt = 0;
find (\&everyfile, $thisDir);
print "$cnt pictures processed\n";

exit;

__END__

=head1 NAME

create_alternate_pictures.pl

=head1 SYNOPSIS

 create_alternate_pictures.pl [--path=directory-path] \
                   [--debug] [--help] [--manual] 


=head1 DESCRIPTION

This script reads the files in a directory and creates thumbnails,
125X125 pixels max, vga sized images (640X480), and svga sized 
images (800X600).

=head1 OPTIONS AND ARGUMENTS

=over 4

=item --path=directory-path

An optional parameter.  If no directory path is specified then . is
used.

=item --help

Displays help text.

=item --help

Displays more complete help text.

=item --debug

Turns on debugging displays.

=back

=head1 AUTHOR

Bill MacAllister <bill.macallister@prideindustries.com>

=cut

