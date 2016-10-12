# -------------------------------------------------------------------
# This module is used by the Rings gallery application.  See
# below for a more complete description.

package Rings::Common;

use AppConfig qw(:argcount :expand);
use Carp;
use DBI;
use File::Basename;
use File::Slurp;
use File::Spec;
use Getopt::Long;
use Image::ExifTool 'ImageInfo';
use Image::Magick;
use Pod::Usage;
use POSIX ();
use strict;
use Sys::Syslog qw(:standard :macros);
use Time::Local;

BEGIN {

    use Exporter();

    our @ISA    = qw(Exporter);
    our @EXPORT = qw(
      $CONF
      $DBH
      $DBH_UPDATE
      db_connect
      db_disconnect
      dbg
      check_picture_size
      create_picture_dirs
      create_picture
      get_config
      get_meta_data
      get_next_id
      get_picture_sizes
      get_picture_types
      msg
      pid_to_path
      sql_datetime
      store_meta_data
      trim
      unix_seconds
    );

    our $VERSION = '1.1';

}

our $CONF;
our $DBH;
our $DBH_UPDATE;

# ------------------------------------------------------------------------
# Open up connections to the MySQL data

sub db_connect {
    my $dbi
      = 'dbi:mysql:host='
      . $CONF->db_host . ';'
      . 'database='
      . $CONF->db_name;
    my %attr = (PrintError => 1, RaiseError => 1);
    $DBH = DBI->connect($dbi, $CONF->db_user, $CONF->db_password, \%attr)
      or die "ERROR: Can't connect to database $dbi for read\n";
    $DBH_UPDATE
      = DBI->connect($dbi, $CONF->db_user, $CONF->db_password, \%attr)
      or die "ERROR: Can't connect to database $dbi for update\n";
    return;
}

# ------------------------------------------------------------------------
# disconnect from database

sub db_disconnect {
    $DBH->disconnect or die "ERROR: Database disconnect failed (read)";
    $DBH_UPDATE->disconnect
      or die "ERROR: Database disconnect failed (update)";
    return;
}

# ------------------------------------------------------------------------
# output debugging information

sub dbg {
    (my $tmp) = @_;
    msg('debug', $tmp);
    return;
}

# ------------------------------------------------------------------------
# read configuration files and get options

sub get_config {
    my ($conf_file) = @_;

    my $global_config = '/etc/rings/rings.conf';

    # Define the properties
    $CONF = AppConfig->new({});

    # Define the properties
    $CONF->define(
        'db_credentials=s',
        {
            DEFAULT  => '/etc/rings/rings.conf',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'db_host=s',
        {
            DEFAULT  => '/etc/rings/rings.conf',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define('db_name', { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define('debug',   { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define(
        'default_group_id',
        {
            DEFAULT  => 'new',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'default_display_size',
        {
            DEFAULT  => 'larger',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'default_display_grade',
        {
            DEFAULT  => 'A',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'default_display_seconds',
        {
            DEFAULT  => '4',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'default_button_position',
        {
            DEFAULT  => 'top',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'default_button_type=s',
        {
            DEFAULT  => 'graphic',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'picture_root',
        {
            DEFAULT  => '/srv/rings',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'default_public',
        {
            DEFAULT  => 'Y',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'syslog',
        {
            DEFAULT  => 'local3',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );

    # Read preferences files in order from global location,
    # home directory, and command line.
    my @confs = ();
    push @confs, $global_config;
    push @confs, $conf_file;
    if ($ENV{'HOME'}) {
        push @confs, $ENV{'HOME'} . '/.rings.conf';
    }
    foreach my $z (@confs) {
        $CONF->file($z) if -e $z;
    }

    # Read db preferences if they exist
    if (-e $CONF->db_credentials) {
        $CONF->define('db_user',     { ARGCOUNT => ARGCOUNT_ONE });
        $CONF->define('db_password', { ARGCOUNT => ARGCOUNT_ONE });
        my $db_conf = get_db_config($CONF->db_credentials);
        $CONF->db_user($db_conf->db_user);
        $CONF->db_password($db_conf->db_password);
    } else {
        msg('fatal',
            'db_credentials file not found (' . $CONF->db_credentials . ')');
    }

    if ($CONF->syslog) {
        openlog($CONF->syslog, 'pid', $CONF->syslog);
        if ($CONF->debug) {
            msg('info', 'logging to syslog ' . $CONF->syslog);
        }
    }

    return;
}

# ------------------------------------------------------------------------
# Read the db configuration file

sub get_db_config {
    my ($conf_file) = @_;
    my $db_conf = AppConfig->new({});
    $db_conf->define('db_user',     { ARGCOUNT => ARGCOUNT_ONE });
    $db_conf->define('db_password', { ARGCOUNT => ARGCOUNT_ONE });
    $db_conf->file($conf_file);
    return $db_conf;
}

# ------------------------------------------------------------------------
# get next id

sub get_next_id {

    (my $id) = @_;

    my $return_number = "NEXT-NUMBER-FAILED";

    my $sel = 'SELECT next_number FROM next_number WHERE id=? ';
    dbg($sel) if $CONF->debug;
    my $sth = $DBH->prepare($sel);
    $sth->execute($id);

    my $cnt = 0;
    while (my $row = $sth->fetchrow_hashref('NAME_lc')) {
        $return_number = $row->{next_number} + 1;
        my $cmd = 'UPDATE next_number SET next_number=? WHERE id=? ';
        dbg($cmd) if $CONF->debug;
        my $sth_update = $DBH_UPDATE->prepare($cmd);
        $sth_update->execute($return_number, $id)
          or die "Error updating next number for $id: $DBH::errstr\n";
        $cnt++;
    }
    if ($cnt == 0) {
        $return_number = 1;
        my $cmd = 'INSERT INTO  next_number (id,next_number) VALUES (?,?) ';
        dbg($cmd) if $CONF->debug;
        if ($CONF->update) {
            my $sth_update = $DBH_UPDATE->prepare($cmd);
            $sth_update->execute($id, $return_number);
        }
    }

    return $return_number;

}

# ------------------------------------------------------------------------
# Generate a useful message

sub msg {
    my ($severity, $msg) = @_;
    my @lines = split /\n/, $msg;
    foreach my $l (@lines) {
        print uc($severity) . " $l\n" or die "ERROR writing msg\n";
        if ($CONF->syslog) {
            syslog('info', uc($severity) . " $l");
        }
    }
    croak $msg if $severity eq 'fatal';
    return;
}

# ------------------------------------------------------------------------
# sql date time string from unix time stamp

sub sql_datetime {

    my ($dt) = @_;

    if (!$dt) {
        $dt = time;
    }
    my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst)
      = localtime($dt);
    $mon++;
    $year += 1900;

    return sprintf("%04d-%02d-%02d %02d:%02d:%02d",
        $year, $mon, $mday, $hour, $min, $sec);
}

# ------------------------------------------------------------------------
# Selecting the next picture in a ring requires that the date_taken
# and the picture_sequence pair be unique. This routine searches the
# existing picture database and returns the number of entries for a
# given date +1.

sub get_picture_sequence {
    my ($dt) = @_;
    my $seq  = 1;
    my $sel  = "SELECT pid FROM pictures_information WHERE date_taken = ? ";
    my $sth  = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($dt);
    while (my $row = $sth->fetchrow_hashref) {
        $seq++;
    }
    return $seq;
}

# ------------------------------------------------------------------------
# Get meta data from picture and return a hash with the data.

sub get_meta_data {

    my ($in_blob) = @_;

    # Data returned will be passed in a hash
    my %ret = ();
    $ret{'picture'} = $in_blob;

    # Get picture meta data
    my @blob;
    $blob[0] = $ret{'picture'};
    my $pic = Image::Magick->New();
    $pic->BlobToImage(@blob);

    my $info = ImageInfo(\@blob[0]);
    foreach my $t (keys %{$info}) {
	$t =~ s/^\s+|\s+$//g;
	$ret{$t} = ${$info}{$t};
	if ($CONF->debug) {
	    dbg("$t = $ret{$t}");
	}
    }

    return %ret;
}

# ------------------------------------------------------------------------
# Store meta data for a picture

sub store_meta_data {

    my ($pid, $in_file, $meta_data_ref) = @_;
    my %meta = %{$meta_data_ref};
    my $ts   = sql_datetime();
    
    dbg(" Storing meta data for $in_file");

    # Set file paths and names
    $meta{'source_path'} = File::Spec->rel2abs($in_file);
    ($meta{'source_file'}, $meta{'source_dirs'}, $meta{'source_suffix'})
      = fileparse($meta{'source_path'});
    my @dirs = split(/\//, $meta{'source_dirs'});
    $meta{'picture_lot'} = @dirs[-1];
	
    # Set default time stamp
    if (!$meta{'datetime'}) {
        $meta{'datetime'} = $ts;
    }

    # Store summary meta data
    my $sel = "SELECT pid FROM pictures_information WHERE pid=? ";
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($pid);
    my $row_found;
    while (my $row = $sth->fetchrow_hashref) { $row_found = 1; }
    if ($row_found) {
        my $cmd = "UPDATE pictures_information SET ";
        $cmd .= 'picture_date = ?, ';
        $cmd .= 'raw_picture_size = ?, ';
        $cmd .= 'raw_signature = ?, ';
        $cmd .= 'camera = ?, ';
        $cmd .= 'shutter_speed = ?, ';
        $cmd .= 'fstop = ?, ';
        $cmd .= 'date_last_maint = ?';
        $cmd .= 'WHERE pid = ? ';
        my $sth_update = $DBH_UPDATE->prepare($cmd);

        if ($CONF->debug) {
            dbg($cmd);
        }
        $sth_update->execute($meta{'datetime'}, $meta{'size'},
	    $meta{'signature'}, $meta{'camera'}, $meta{'shutterspeed'},
            $meta{'fnumber'}, $ts, $pid);
    } else {
        # Get a picture sequence number
        my $picture_sequence = get_picture_sequence($meta{'datetime'});
        my $cmd              = "INSERT INTO pictures_information SET ";
        $cmd .= 'pid = ?, ';
        $cmd .= 'picture_lot = ?, ';
        $cmd .= 'date_taken = ?, ';
        $cmd .= 'picture_date = ?, ';
        $cmd .= 'picture_sequence = ?, ';
        $cmd .= 'source_file = ?, ';
        $cmd .= 'file_name = ?, ';
        $cmd .= 'raw_picture_size = ?, ';
        $cmd .= 'raw_signature = ?, ';
        $cmd .= 'camera = ?, ';
        $cmd .= 'shutter_speed = ?, ';
        $cmd .= 'fstop = ?, ';
        $cmd .= 'grade = ?, ';
        $cmd .= 'public = ?, ';
        $cmd .= 'date_last_maint = ?, ';
        $cmd .= 'date_added = ? ';
        my $sth_update = $DBH_UPDATE->prepare($cmd);

        if ($CONF->debug) {
            dbg($cmd);
        }
        $sth_update->execute(
            $pid,                         $meta{'picture_lot'},
            $meta{'datetime'},            $meta{'datetime'},
            $picture_sequence,            $meta{'source_path'},
            $meta{'source_file'},         $meta{'size'},
	    $meta{'signature'},           $meta{'Model'},
            $meta{'ExposureTime'},        $meta{'FNumber'},
            $CONF->default_display_grade, $CONF->default_public,
            $ts,                          $ts
        );
    }

    # Clean out any existing metadata for this picture
    my $exif_clean_cmd = 'DELETE FROM pictures_exif where pid = ?';
    my $exif_del = $DBH_UPDATE->prepare($exif_clean_cmd);
    if ($CONF->debug) {
	dbg($exif_clean_cmd);
    }
    $exif_del->execute($pid);

    # Store all of the meta data extracted from the image
    my $exif_insert_cmd = 'INSERT INTO pictures_exif SET ';
    $exif_insert_cmd .= 'pid = ?, ';
    $exif_insert_cmd .= 'exif_key = ?, ';
    $exif_insert_cmd .= 'exif_value = ?, ';
    $exif_insert_cmd .= 'date_last_maint = ?, ';
    $exif_insert_cmd .= 'date_added = ? ';
    my $exif_insert = $DBH_UPDATE->prepare($exif_insert_cmd);
    if ($CONF->debug) {
	dbg($exif_insert_cmd);
    }
    for my $k (sort keys %meta) {
	$exif_insert->execute($pid, $k, $meta{$k}, $ts, $ts);
    }
 
    return;
}

# ------------------------------------------------------------------------
# Resize a picture, store some meta data, and return the resized
# picture

sub create_picture {

    my ($this_pid, $this_size_id, $this_picture, $this_type) = @_;

    my $max_x;
    my $max_y;
    my $table;
    my $sel = 'SELECT * FROM picture_sizes WHERE size_id = ?';
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($this_size_id);
    if (my $row = $sth->fetchrow_hashref) {
        $max_y = $row->{max_height};
        $max_x = $row->{max_width};
        $table = $row->{table};
    }
    if (!$table) {
        msg('fatal', "Invalid size id: $this_size_id");
    }

    my @blob;
    $blob[0] = $this_picture;
    my $this_pic = Image::Magick->New();
    $this_pic->BlobToImage(@blob);
    my $width  = $this_pic->Get('width');
    my $height = $this_pic->Get('height');

    # Resize picture if requested
    my $ret_pic;
    if ($max_x == 0 || $max_y == 0) {
        $ret_pic = $this_picture;
    } else {
        my $newPic = $this_pic->Clone();
        my $x      = $width;
        my $y      = $height;
        my $x1     = $max_x;
        my $y1     = int(($x1 / $width) * $height);
        my $y2     = $max_y;
        my $x2     = int(($y2 / $height) * $width);
        if ($x1 < $x2) {
            $x = $x1;
            $y = $y1;
        } else {
            $x = $x2;
            $y = $y2;
        }
        $width  = int($x);
        $height = int($y);
        dbg("  Producing picture $width by $height");
        $newPic->Resize(width => $x, height => $y);
        my @bPic = $newPic->ImageToBlob();
        $ret_pic = $bPic[0];
    }

    my $sel = "SELECT pid FROM $table ";
    $sel .= "WHERE pid=? ";
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($this_pid);

    my $row = $sth->fetchrow_hashref;

    if ($row->{pid} != $this_pid && $this_pid > 0) {

        my $cmd = "INSERT INTO $table (";
        $cmd .= 'pid,';
        $cmd .= 'picture_type,';
        $cmd .= 'width,';
        $cmd .= 'height,';
        $cmd .= 'size,';
        $cmd .= 'date_last_maint,';
        $cmd .= 'date_added';
        $cmd .= ') VALUES (?,?,?,?,?,?,?) ';
        my $sth_update = $DBH_UPDATE->prepare($cmd);

        if ($CONF->debug) {
            dbg($cmd);
        }
        $sth_update->execute($this_pid, $this_type, $width, $height,
            length($ret_pic), sql_datetime(), sql_datetime());

    } elsif ($this_pid > 0) {

        my $cmd = "UPDATE $table SET ";
        $cmd .= 'picture_type = ?,';
        $cmd .= 'width = ?,';
        $cmd .= 'height = ?,';
        $cmd .= 'size = ?,';
        $cmd .= 'date_last_maint = ? ';
        $cmd .= 'WHERE pid = ? ';
        my $sth_update = $DBH_UPDATE->prepare($cmd);

        if ($CONF->debug) {
            dbg($cmd);
        }
        $sth_update->execute($this_type, $width, $height, length($ret_pic),
            sql_datetime(), $this_pid);
    }
    return $ret_pic;
}

# ------------------------------------------------------------------------
# trim leading and trailing white space

sub trim {
    my ($out) = @_;
    $out =~ s/^\s+//;
    $out =~ s/\s+$//;
    return $out;
}

# ------------------------------------------------------------------------
# unix time stamp from sql date time string

sub unix_seconds {

    my ($dt) = @_;

    my $ret = time;
    if ($dt =~ m/(\d+)\-(\d+)\-(\d+)\s+(\d+):(\d+):(\d+)/) {
        my $yyyy = $1;
        my $mm   = $2;
        my $dd   = $3;
        my $h    = $4;
        my $m    = $5;
        my $s    = $6;
        $mm--;
        $ret = timelocal($s, $m, $h, $dd, $mm, $yyyy);
    }
    return $ret;
}

# ------------------------------------------------------------------------
# Create directories need to store a picture

sub _create_dir {
    my ($this_dir) = @_;
    $this_dir =~ s{//}{/}xmsg;
    if (!-e $this_dir) {
        mkdir $this_dir;
        if (!-d $this_dir) {
            msg('fatal', "Problem creating directory $this_dir");
        }
    }
    return $this_dir;
}

# ------------------------------------------------------------------------
# Create directories need to store a picture

sub create_picture_dirs {
    my ($group, $this_size) = @_;
    if (!check_picture_size($this_size)) {
        msg('fatal', "Invalid size: $this_size");
    }

    my $output_root = $CONF->picture_root;
    $output_root = _create_dir($output_root);

    $output_root .= "/$group";
    $output_root = _create_dir($output_root);

    $output_root = "$output_root/$this_size";
    $output_root = _create_dir($output_root);

    return $output_root;
}

# ------------------------------------------------------------------------
# valid the picure size

sub check_picture_size {
    my ($this_id) = @_;

    my $sel = 'SELECT * FROM picture_sizes where size_id = ?';
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($this_id);
    my $size_found;
    while (my $row = $sth->fetchrow_hashref) {
        $size_found++;
    }
    return $size_found;
}

# ------------------------------------------------------------------------
# get picture sizes to generate

sub get_picture_sizes {
    my @flds   = ('max_height', 'max_width', 'table', 'description');
    my $sel    = 'SELECT * FROM picture_sizes';
    my $sth    = $DBH->prepare($sel);
    my %psizes = ();
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute();
    while (my $row = $sth->fetchrow_hashref) {
        for my $f (@flds) {
            $psizes{ $row->{size_id} }{$f} = ${$row}{$f};
        }
    }
    return %psizes;
}

# ------------------------------------------------------------------------
# get picture types

sub get_picture_types {
    my $sel        = 'SELECT * FROM picture_types';
    my $sth        = $DBH->prepare($sel);
    my %mime_types = ();
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute();
    while (my $row = $sth->fetchrow_hashref) {
        $mime_types{ $row->{mime_type} } = $row->{file_type};
    }
    return %mime_types;
}

# ------------------------------------------------------------------------
# Return the path to a file given the pid, group, size, and type desired

sub pid_to_path {
    my ($pid, $group, $side_id, $type) = @_;

    if (!check_picture_size($side_id)) {
        msg('fatal', "Invalid size: $side_id");
    }

    $type =~ s/[.]//xmsg;

    my $path = $CONF->picture_root;
    $path .= "/${group}/${side_id}/${pid}.${type}";
    $path =~ s{//}{/}xmsg;

    return $path;
}

# ------------------------------------------------------------------------
# Make sure that the parameters passed to a routine are valid

sub validate_params {
    my ($name, $in_ref, $valid_ref) = @_;
    my %in        = %$in_ref;
    my @valid     = @$valid_ref;
    my %validList = ();
    foreach my $v (@valid) { $validList{$v}++; }
    foreach my $p (sort keys %in) {
        if (!$validList{$p}) {
            dbg("INVALID PARAMETER $p passed to $name");
        } else {
            dbg("$p = '$in{$p}' passed to $name");
        }
    }
    return;
}

END { }

1;

__END__

=head1 NAME

whm::rings - Utility routines for the rings gallery application

=head1 SYNOPSIS

=head1 AUTHOR

Bill MacAllister <bill@ca-zephyr.org>

=head1 COPYRIGHT

This software was developed for use by Bill MacAllister.  All rights
reserved.

=cut
