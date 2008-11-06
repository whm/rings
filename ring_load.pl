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
             %tableList
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
# store pictures

sub store_picture {

    my ($thisPID, 
        $thisTable, 
        $thisPicture,
        $thisType) = @_;
    
    debug_output (" Processing $thisPID $thisTable");
    
    my @blob;
    $blob[0] = $thisPicture;
    my $thisPic = Image::Magick->New();
    $thisPic->BlobToImage(@blob);
    
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

    # default to moderate
    my $max_x = 640;
    my $max_y = 480;

    if ($thisTable eq 'pictures_large') {
        $max_x = 640;
        $max_y = 480;
    } elsif ($thisTable eq 'pictures_larger') {
        $max_x = 800;
        $max_y = 600;
    } elsif ($thisTable eq 'pictures_1280_1024') {
        $max_x = 1280;
        $max_y = 1024;
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
    my $ptype   = "image/$compression";
    
    # -- Store the raw image

    my @bPic = $thisPic->ImageToBlob();
    my $cmd = "INSERT INTO pictures_raw SET ";
    $cmd .= "pid = ?,";
    $cmd .= "picture = ?,";
    $cmd .= "picture_type = ?,";
    $cmd .= "height = ?,";
    $cmd .= "width = ?,";
    $cmd .= "date_last_maint = ?,";
    $cmd .= "date_added = ? ";
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

    foreach my $t (sort keys %tableList) {
        store_picture ($pid, 
                       $t,
                       $bPic[0], 
                       $ptype);
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

    my $cmd = "INSERT INTO pictures_information SET ";
    $cmd .= "pid = ?,";
    $cmd .= "picture_date = ?,";
    $cmd .= "picture_sequence = ?,";
    $cmd .= "date_taken = ?,";
    $cmd .= "file_name = ?,";
    $cmd .= "key_words = ?,";
    $cmd .= "taken_by = ?,";
    $cmd .= "date_last_maint = ?,";
    $cmd .= "date_added = ?";
    if ($opt_debug) {debug_output($cmd);}
    if ($opt_update) {
        my $sth_update = $dbh_update->prepare ($cmd);
        $sth_update->execute($pid,
                             $pic_datetime,
                             $cnt, 
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
}

# -------------
# Main routine
# -------------

print ">>> ring_load.pl                    v: 3-Sep-2007\n";

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
    pod2usage(-verbose => 2);
}

%tableList = ('pictures_small' => 1,
              'pictures_large' => 1,
              'pictures_larger' => 1,
              'pictures_1280_1024' => 1);

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

if (length($opt_host) == 0)    {$opt_host    = $prefs{'host'};}
if (length($opt_db) == 0)      {$opt_db      = $prefs{'db'};}
if (length($opt_keyword) == 0) {$opt_keyword = $prefs{'keyword'};}
if (length($opt_pass) == 0)    {$opt_pass    = $prefs{'pass'};}
if (length($opt_path) == 0)    {$opt_path    = $prefs{'path'};}
if (length($opt_ppe) == 0)     {$opt_ppe     = $prefs{'ppe'};}
if (length($opt_user) == 0)    {$opt_user    = $prefs{'user'};}
if (length($opt_photographer) == 0) {
    $opt_photographer = $prefs{'photographer'};
}

if (length($opt_host) == 0) {
    $opt_host = 'localhost';
}
if (length($opt_db) == 0) {
    $opt_db = 'rings';
}
if (length($opt_ppe) == 0)     {$opt_ppe = 'new';}

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

This script reads the jpeg files in a directory and loads the rings
database.  Most command line options can also be specified in a 
preferences file, ~/.rings.

=head1 OPTIONS AND ARGUMENTS

=over 4

=item --path=directory-path

An optional parameter.  If no directory path is specified then . is
used.

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

A string representing the starting date and time.  If not is specified
the current date and time is used as a starting point and incremented
by 1 second for each picture.

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

Bill MacAllister <bill@macallister.grass-valley.ca.us>

=cut

