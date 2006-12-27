#!/usr/bin/perl

use strict;
use Cwd;
use DBI;
use Getopt::Long;
use Image::Magick;
use Pod::Usage;
use Time::Local;

use vars qw (
             %prefs
             $cnt
             $dbh
             $dbh_update
             $debug
             $debug_time
             $flds
             $last_datetime
             $opt_datetaken
             $opt_db
             $opt_debug
             $opt_host
             $opt_help
             $opt_keyword
             $opt_manual
             $opt_pass
             $opt_path
             $opt_photographer
             $opt_ppe
             $opt_user
             $opt_update
             $timeStamp
             $vals
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

# -------------------------------------------------------------
# get next id

sub get_next {
    
    (my $id) = @_;
    
    my $return_number = "NEXT-NUMBER-FAILED";
    
    my $sel = "SELECT next_number FROM next_number WHERE id='$id' ";
    if ($debug) {debug_output ($sel);}
    my $sth = $dbh->prepare ("$sel");
    $sth->execute();
    
    while (my $row = $sth->fetchrow_hashref('NAME_lc') ) {
        $return_number = $row->{next_number};
        if ($return_number > 0) {
            my $nxt = $return_number + 1;
            my $cmd = "UPDATE next_number SET next_number=$nxt ";
            $cmd .= "WHERE id='$id' ";
            if ($debug) {debug_output ($cmd);}
            my $sth_update = $dbh_update->prepare ("$cmd");
            $sth_update->execute();
        } else {
            my $nxt = 1;
            my $cmd = "INSERT INTO  next_number (id,next_number) ";
            $cmd .= "VALUES ('$id',$nxt) ";
            $cmd = "UPDATE next_number SET next_number=$nxt WHERE id='$id' ";
            if ($debug) {debug_output ($cmd);}
            if ($opt_update) {
                my $sth_update = $dbh_update->prepare ("$cmd");
                $sth_update->execute();
            }
            $return_number = $nxt;
        }
    }
    
    return $return_number;
    
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

# -------------------------------------------------------------
#  construct an flds and values for an insert
# 
#   $in_type == "n" is a number
#   $in_type != "n" anything else is a string

sub mkin {
    
    (my $a_fld, my $a_val, my $in_type) = @_;
    
    if (length($a_val) > 0) {
        my $c = "";
        if (length($flds) > 0) {$c = ",";}
        $flds .= $c . $a_fld;
        if ( $in_type ne "n" ) {
            $a_val = $dbh_update->quote($a_val);
        }
        $vals .= $c . $a_val;
    }
    return;
}

# ------------------------------------------------
# process the files

sub save_file {
    
    (my $a_file) = @_;
    
    my $pid = 1;
    if ($opt_update) {
        $pid = get_next("pid");
    }
    debug_output (" Creating elements for picture record $pid");
    
    my $a_filename = $a_file;
    $a_filename =~ s/^[\/]+//;
    while ($a_filename =~ s/^.*?\///) {}

    my $a_filetype = '';
    if ($a_filename =~  /^(.*?)\.(.*)/) {
        $a_filename = $1;
        $a_filetype = $2;
    }
    
    $cnt++;
    
    # -- read the image
    my $thisPic = new Image::Magick;
    $thisPic->Read($a_file);
    my ($width, 
        $height, 
        $size, 
        $format, 
        $compression,
        $camera,
        $this_datetime,
        $this_shutterspeed,
        $this_fnumber) 
        = $thisPic->Get('width',
                        'height',
                        'filesize',
                        'format',
                        'compression',
                        '%[EXIF:Model]',
                        '%[EXIF:DateTime]',
                        '%[EXIF:ExposureTime]',
                        '%[EXIF:FNumber]');
    if ($opt_debug) {
        debug_output ("      format: $format");
        debug_output (" compression: $compression");
        debug_output ("       width: $width");
        debug_output ("      height: $height");
        debug_output ("       model: $camera");
        debug_output ("    datetime: $this_datetime");
        debug_output ("exposuretime: $this_shutterspeed");
        debug_output ("     fnumber: $this_fnumber");
    }


    # -- Make sure it is a jpeg image
    
    if ($compression ne 'JPEG') {
        $thisPic->Set(compression=>'JPEG');
        $compression = 'JPEG';
    }
    my $newSVGA   = $thisPic->Clone();
    my $newVGA    = $thisPic->Clone();
    my $newThumb  = $thisPic->Clone();
    my $ptype   = "image/$compression";
    
    # -- Store the raw image

    my @bPic = $thisPic->ImageToBlob();
    my $cmd = "INSERT INTO pictures_raw (";
    $cmd .= "pid,";
    $cmd .= "picture,";
    $cmd .= "picture_type,";
    $cmd .= "height,";
    $cmd .= "width,";
    $cmd .= "date_last_maint,";
    $cmd .= "date_added";
    $cmd .= ") VALUES (";
    $cmd .= "?,?,?,?,?,?,?) ";
    if ($opt_debug) {debug_output($cmd);}
    if ($opt_update) {
        my $sth_update = $dbh_update->prepare ($cmd);
        $sth_update->execute($pid,
                             $bPic[0], 
                             $ptype,
                             $height,
                             $width,
                             sql_datetime(),
                             sql_datetime());
    }

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
    
    # -- Store SVGA

    my @bSVGA = $newSVGA->ImageToBlob();
    my $cmd = "INSERT INTO pictures_larger (";
    $cmd .= "pid,";
    $cmd .= "picture,";
    $cmd .= "picture_type,";
    $cmd .= "height,";
    $cmd .= "width,";
    $cmd .= "date_last_maint,";
    $cmd .= "date_added";
    $cmd .= ") VALUES (";
    $cmd .= "?,?,?,?,?,?,?) ";
    if ($opt_debug) {debug_output($cmd);}
    if ($opt_update) {
        my $sth_update = $dbh_update->prepare ($cmd);
        $sth_update->execute($pid,
                             $bSVGA[0], 
                             $ptype,
                             $y,
                             $x,
                             sql_datetime(),
                             sql_datetime());
    }

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

    # Store VGA

    my @bVGA = $newVGA->ImageToBlob();
    my $cmd = "INSERT INTO pictures_large (";
    $cmd .= "pid,";
    $cmd .= "picture,";
    $cmd .= "picture_type,";
    $cmd .= "height,";
    $cmd .= "width,";
    $cmd .= "date_last_maint,";
    $cmd .= "date_added";
    $cmd .= ") VALUES (";
    $cmd .= "?,?,?,?,?,?,?) ";
    if ($opt_debug) {debug_output($cmd);}
    if ($opt_update) {
        my $sth_update = $dbh_update->prepare ($cmd);
        $sth_update->execute($pid,
                             $bVGA[0], 
                             $ptype,
                             $y,
                             $x,
                             sql_datetime(),
                             sql_datetime());
    }

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
    
    my @bThumb = $newThumb->ImageToBlob();

    my $cmd = "INSERT INTO pictures_small (";
    $cmd .= "pid,";
    $cmd .= "picture,";
    $cmd .= "picture_type,";
    $cmd .= "height,";
    $cmd .= "width,";
    $cmd .= "date_last_maint,";
    $cmd .= "date_added";
    $cmd .= ") VALUES (";
    $cmd .= "?,?,?,?,?,?,?) ";
    if ($opt_debug) {debug_output($cmd);}
    if ($opt_update) {
        my $sth_update = $dbh_update->prepare ($cmd);
        $sth_update->execute($pid,
                             $bThumb[0], 
                             $ptype,
                             $y,
                             $x,
                             sql_datetime(),
                             sql_datetime());
    }

    # -- Create information record

    if (length($this_datetime) == 0) {
        $this_datetime = sql_datetime($timeStamp);
    }
    my $pic_datetime = $this_datetime;
    if ($this_datetime == $last_datetime) {
        $pic_datetime = sprintf("%s.%3.3d", $this_datetime, $cnt);
        $pic_datetime =~ s/\.\./\./g;
    }
    $last_datetime = $this_datetime;

    my $cmd = "INSERT INTO pictures_information (";
    $cmd .= "pid,";
    $cmd .= "date_taken,";
    $cmd .= "file_name,";
    $cmd .= "key_words,";
    $cmd .= "taken_by,";
    $cmd .= "date_last_maint,";
    $cmd .= "date_added";
    $cmd .= ") VALUES (";
    $cmd .= "?,?,?,?,?,?,?) ";
    if ($opt_debug) {debug_output($cmd);}
    if ($opt_update) {
        my $sth_update = $dbh_update->prepare ($cmd);
        $sth_update->execute($pid,
                             $pic_datetime,
                             $a_filename, 
                             $opt_keyword,
                             $opt_photographer,
                             sql_datetime(),
                             sql_datetime());
    }

    if (length($opt_ppe)>0) {
        debug_output (" Creating picture details $pid $opt_ppe");
        $flds = $vals = '';
        mkin ('pid', $pid,     'n');
        mkin ('uid', $opt_ppe, 's');
        my $cmd = "INSERT INTO picture_details ($flds) VALUES ($vals)";
        if ($opt_debug) {debug_output("length of sql command: ".length($cmd));}
        if ($opt_update) {
            my $sth_update = $dbh_update->prepare ($cmd);
            $sth_update->execute();
        }
    }
    
    $timeStamp = $timeStamp + 60;
    
    # -- clean up
    undef $thisPic;
    undef $newSVGA;
    undef $newVGA;
    undef $newThumb;
}

# -------------
# Main routine
# -------------

print ">>> ring_load.pl                    v:30-Nov-2003\n";

# -- get options
GetOptions(
           'datetaken=s'    => \$opt_datetaken,
           'db=s'           => \$opt_db,
           'debug'          => \$opt_debug,
           'help'           => \$opt_help,
           'host=s'         => \$opt_host,
           'keyword=s'      => \$opt_keyword,
           'manual'         => \$opt_manual,
           'pass=s'         => \$opt_pass,
           'path=s'         => \$opt_path,
           'photographer=s' => \$opt_photographer,
           'ppe=s'          => \$opt_ppe,
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
if (length($opt_keyword) == 0) {$opt_keyword = $prefs{'keyword'};}
if (length($opt_pass) == 0)    {$opt_pass = $prefs{'pass'};}
if (length($opt_path) == 0)    {$opt_path = $prefs{'path'};}
if (length($opt_photographer) == 0) {
    $opt_photographer = $prefs{'photographer'};
}
if (length($opt_ppe) == 0)     {$opt_ppe = $prefs{'ppe'};}
if (length($opt_user) == 0)    {$opt_user = $prefs{'user'};}

if (length($opt_host) == 0) {
    $opt_host = 'localhost';
}
if (length($opt_db) == 0) {
    $opt_db = 'rings';
}

$timeStamp = unix_seconds($opt_datetaken);
if ($opt_debug) {debug_output("starting timestamp: $timeStamp");}

if (length($opt_keyword) == 0) {
    $opt_keyword = 'NEWPICTURE';
    print "    Using keyword of NEWPICTURE\n";
}

if (length($opt_pass) == 0) {
    print "%MAC-F-PASSREQ, a MySQL password is required\n";
    pod2usage(-verbose => 1);
    exit;
}
if (length($opt_user) == 0) {
    print "%MAC-F-USERREQ, a MySQL username is required\n";
    pod2usage(-verbose => 1);
    exit;
}

if ($opt_debug) {debug_output ("Initialize timer.");}

# -- Open up connections to the MySQL data

my $dbi = "dbi:mysql:host=$opt_host;database=$opt_db";
$dbh = DBI->connect ($dbi, $opt_user, $opt_pass)
    or die "%MAC-F-CANTCONN, Can't connect to database $dbi for read\n";
$dbh->{LongTruncOk} = 1;
$dbh->{LongReadLen} = 100000;
$dbh_update = DBI->connect ($dbi, $opt_user, $opt_pass)
    or die "%MAC-F-CANTCONN, Can't connect to database $dbi for update\n";

my $thisDir = './';
if (length($opt_path)>0 ) {
    $thisDir = $opt_path;
}

my $typeList = '(gif|jpeg|jpg|png)';
print "Examining files that match $thisDir*.$typeList\n";

$cnt = 0;
my @fileList = glob ($thisDir.'*');
foreach my $f (@fileList) {
    if ( $f =~ /(.*?)\.$typeList$/i ) {
        if ($opt_debug) {debug_output ("    Saving file: $f");}
        save_file($f);
    }
}

print "$cnt pictures processed\n";

$dbh->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (read)";
$dbh_update->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (update)";

exit;

__END__

=head1 NAME

ring_load.pl

=head1 SYNOPSIS

 ring_load.pl [--path=directory-path] [--update] \
              [--host=mysql-host] [--db=dbname] \
              --user=mysql-username --pass=mysql-password \
              [--keyword=string] [--datetaken=string] \
              [--ppe=string] [--photographer=string] \
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

This script reads the files in a directory and creates thumbnails, 
50X50 pixels max, and vga sized images, 640X480 max images.  All images are
loaded in the rings database.

=head1 OPTIONS AND ARGUMENTS

=over 4

=item --path=directory-path

An optional parameter.  If no directory path is specified then . is used.

=item --host=mysql-hostname

MySQL host name.  If not specified then localhost is used.

=item --db=database-name

MySQL database name.  If not specified then rings is used.

=item --user=mysql-username

MySQL username.  Required.

=item --host=mysql-password

MySQL password.  Required.

=item --keyword=string

A string of keywords.  If not is specified then "NEW" is used.

=item --datetaken=string

A string representing the date and time.  If not is specified the
current date and time is used as a starting point and incremented by
1 second for each picture.

=item --photographer=string

The photograper's name.  If none is specified then null.

=item --ppe=string

Picture, place or event.

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

