#!/usr/bin/perl

use strict;
use CGI;                             # load CGI routines
use DBI;
use Image::Magick;

# -------------
# Main routine
# -------------

my $q = new CGI;                        # create new CGI object

#-- Get input from the web
my $pid    = $q->param ("PICTURE_ID");
my $psize  = $q->param( "PICTURE_GEOMETRY" );
my $pscale = $q->param( "PICTURE_SCALE" );
my $pmax_x = $q->param( "PICTURE_MAX_X" );
my $pmax_y = $q->param( "PICTURE_MAX_Y" );

my $mysql_host = 'localhost';
my $mysql_user = 'mac';
my $mysql_pass = 'wardetee';
my $mysql_db   = 'rings';

# -- Open up connections to the MySQL data
my $dbi = "dbi:mysql:$mysql_db";
my $dbh = DBI->connect ($dbi, $mysql_user, $mysql_pass)
    or die "%pride-f-cantconn, Can't connect to database $dbi\n";

# -- setup statement to look up work orders
my $sel = "SELECT ";
$sel .= "picture, ";
$sel .= "picture_type ";
$sel .= "FROM pictures ";
$sel .= "WHERE pid='$pid' ";
my $sth = $dbh->prepare ("$sel");
$sth->execute();

my $cnt = 1;
my $pblob = '';
my $image = new Image::Magick;
if (my $row = $sth->fetchrow_hashref('NAME_lc') ) {
    # -- get the image and type from the database
    $pblob = $row->{picture};
    my ($width, $height, $size, $format) = $image->Ping(blob=>$pblob);
    my $ptype = $row->{picture_type};
    $ptype = "image/$format";

    # -- convert it to an ImageMagick object to allow us to muck with it
    $image->BlobToImage($pblob);

    if ( length($psize)>0 ) {
	$image->Resize(width=>"$psize");
    } elsif ( length($pscale)>0 ) {
	$width = $pscale * $width;
	$height = $pscale * $height;
	$image->Resize(width=>$width, height=>$height );
    } elsif ( $pmax_x+$pmax_y > 0 ) {
	my $x = $width;
	my $y = $height;
	my $x1 = $width;
	my $y1 = $height;
	my $x2 = $width;
	my $y2 = $height;
	if (($pmax_x > 0) & ($pmax_x < $width)) {
	    $x1 = $pmax_x;
	    $y1 = ($x1/$width) * $height;
	    $x = $x1;
	    $y = $y1;
	}
	if (($pmax_y > 0) & ($pmax_y < $height)) {
	    $y2 = $pmax_y;
	    $x2 = ($y2/$height) * $width;
	    $x = $x2;
	    $y = $y2;
	}
	if ( (($pmax_x>0) & ($pmax_y>0)) 
           & (($pmax_x<$width) || ($pmax_y<$height)) ) {
	    if ($x1 < $x2) {
		$x = $x1;
		$y = $y1;
	    } else {
		$x = $x2;
		$y = $y2;
	    }
	}
	$image->Resize(width=>$x, height=>$y);
    }

    # -- now convert it back to a blob to display it
    my @blobs = $image->ImageToBlob();

    # -- spit it out
    $| = 1;
    print $q->header(-type=>"$ptype",-expires=>'+3d');
    print $blobs[0];
} else {
    #-- Open an a document
    print $q->header,                      # create the HTTP header
          $q->start_html('Display Error'), # start the HTML
          $q->h1('Display Error');         # level 1 header
    print "Problem SQL:\n";
    print "<p>\n";
    print "$sel<br>\n";
    print $q->end_html;                  # end the HTML
}

$sth->finish();
$dbh->disconnect
    or die "pride-f-disconfail, Disconnect failed for $dbi";

exit;
