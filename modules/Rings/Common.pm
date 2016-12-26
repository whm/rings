# -------------------------------------------------------------------
# Copyright (c) 2016, Bill MacAllister <bill@ca-zephyr.org>
# File: Common.pm
# Description: This module is used by the Rings gallery application.

package Rings::Common;

use AppConfig qw(:argcount :expand);
use Carp;
use DBI;
use File::Basename;
use File::Slurp;
use File::Spec;
use File::Type;
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
      make_picture_path
      msg
      pid_to_path
      queue_error
      queue_action_reset
      queue_action_set
      queue_upload
      queue_upload_error
      queue_upload_reset
      set_new_picture
      sql_datetime
      sql_format_datetime
      store_meta_data
      trim
      unix_seconds
      validate_mime_type
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
        'default_public',
        {
            DEFAULT  => 'Y',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'loop_limit_daemon',
        {
            DEFAULT  => 120,
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'loop_limit_load',
        {
            DEFAULT  => 120,
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
        'queue_sleep',
        {
            DEFAULT  => '30',
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

    # Configuration elements used by the PHP user interface.  Defining
    # them here suppresses perl startup warnings.
    $CONF->define('display_size', { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define('index_size',   { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define('maint_size',   { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define('ring_admin',   { ARGCOUNT => ARGCOUNT_ONE });

    # Read preferences files in order from global location,
    # home directory, and command line.
    my @confs = ();
    push @confs, $global_config;
    push @confs, $conf_file;
    if ($ENV{'HOME'}) {
        push @confs, $ENV{'HOME'} . '/.rings.conf';
    }
    for my $z (@confs) {
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
        msg(
            'fatal',
            'db_credentials file not found (' . $CONF->db_credentials . ')'
        );
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
# set picture in the new picture group

sub set_new_picture {

    (my $pid) = @_;

    my $sel
      = 'INSERT INTO picture_details SET '
      . 'pid = ?, '
      . 'date_last_maint = NOW(), '
      . 'date_added = NOW() '
      . 'ON DUPLICATE KEY UPDATE '
      . 'date_last_maint = NOW() ';
    dbg($sel) if $CONF->debug;
    my $sth_update = $DBH_UPDATE->prepare($sel);
    $sth_update->execute($pid)
      or die "Error updating picture_details for $pid: $DBH::errstr\n";
    return;
}

# ------------------------------------------------------------------------
# Generate a useful message

sub msg {
    my ($severity, $msg) = @_;
    if (!$msg) {
        $msg      = $severity;
        $severity = 'info';
    }
    my @lines = split /\n/, $msg;
    for my $l (@lines) {
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

    return sprintf(
        "%04d-%02d-%02d %02d:%02d:%02d",
        $year, $mon, $mday, $hour, $min, $sec
    );
}

# ------------------------------------------------------------------------
# sql date time string from a string date time

sub sql_format_datetime {

    my ($dt) = @_;

    my $return_dt = $dt;
    my $dt_format = '%0.4i-%0.2i-%0.2i %0.2i:%0.2i:%0.2i';
    if ($dt =~ /^(\d{4}).(\d{1,2}).(\d{1,2}).(\d{1,2}).(\d{1,2}).(\d{1,2})/xms)
    {
        my $yr  = $1;
        my $mon = $2;
        my $day = $3;
        my $hr  = $4;
        my $min = $5;
        my $sec = $6;
        $return_dt = sprintf($dt_format, $yr, $mon, $day, $hr, $min, $sec);
    } elsif ($dt =~ /^(\d{4}).(\d{1,2}).(\d{1,2})/xms) {
        my $yr  = $1;
        my $mon = $2;
        my $day = $3;
        my $hr  = 0;
        my $min = 0;
        my $sec = 0;
        $return_dt = sprintf($dt_format, $yr, $mon, $day, $hr, $min, $sec);
    }
    return $return_dt;
}

# ------------------------------------------------------------------------
# Selecting the next picture in a ring requires that the picture_date
# and the picture_sequence pair be unique. This routine searches the
# existing picture database and returns the number of entries for a
# given date +1.

sub get_picture_sequence {
    my ($dt) = @_;
    my $seq  = 1;
    my $sel  = "SELECT pid FROM pictures_information WHERE picture_date = ? ";
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

    # Get picture meta data
    my @blob;
    $blob[0] = $in_blob;
    my $pic = Image::Magick->New();
    $pic->BlobToImage(@blob);

    # Make sure this data is pulled from the image
    $ret{'ring_width'}       = $pic->Get('width');
    $ret{'ring_height'}      = $pic->Get('height');
    $ret{'ring_size'}        = $pic->Get('filesize');
    $ret{'ring_format'}      = $pic->Get('format');
    $ret{'ring_compression'} = $pic->Get('compression');
    $ret{'ring_signature'}   = $pic->Get('signature');

    # Now pull the rest of the exif data
    my $info    = ImageInfo(\@blob[0]);
    my %meta_lc = ();
    for my $t (keys %{$info}) {
        $t =~ s/^\s+|\s+$//g;
        $ret{$t} = ${$info}{$t};
        $meta_lc{ lc($t) } = ${$info}{$t};
        if ($CONF->debug) {
            dbg("$t = $ret{$t}");
        }
    }

    $ret{'ring_shutterspeed'} = $meta_lc{'shutterspeed'};
    $ret{'ring_fstop'}        = $meta_lc{'fnumber'};

    # Look for some fields under multiple names
    $ret{'ring_camera'} = 'UNKNOWN';
    my @camera_names = ('make', 'model');
    for my $c (@camera_names) {
        if ($meta_lc{$c}) {
            $ret{'ring_camera'} = $meta_lc{$c};
        }
    }

    $ret{'ring_datetime'} = '1948-09-25 09:00:00';
    my @date_names = (
        'datemodify', 'modifydate', 'datetimeoriginal',
        'datecreate', 'createdate'
    );
    for my $n (@date_names) {
        if ($meta_lc{$n}) {
            $ret{'ring_datetime'} = sql_format_datetime($meta_lc{$n});
            if ($CONF->debug) {
                dbg("n = $n, ring_datetime = $ret{'ring_datetime'}");
            }
            last;
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

    if ($CONF->debug) {
        dbg("Storing meta data for $pid");
        for my $a (sort keys %meta) {
            dbg("meta{$a} = $meta{$a}");
        }
    }

    # Set file paths and names
    $meta{'source_path'} = File::Spec->rel2abs($in_file);
    ($meta{'source_file'}, $meta{'source_dirs'}, $meta{'source_suffix'})
      = fileparse($meta{'source_path'});
    my @dirs = split(/\//, $meta{'source_dirs'});
    $meta{'picture_lot'} = @dirs[-1];

    # Store summary meta data
    my $sel = "SELECT * FROM pictures_information WHERE pid=? ";
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($pid);
    my $row_found;
    if (my $row = $sth->fetchrow_hashref) {
        my $cmd = "UPDATE pictures_information SET ";
        $cmd .= 'camera_date = ?, ';
        $cmd .= 'raw_picture_size = ?, ';
        $cmd .= 'raw_signature = ?, ';
        $cmd .= 'camera = ?, ';
        $cmd .= 'shutter_speed = ?, ';
        $cmd .= 'fstop = ?, ';
        $cmd .= 'date_last_maint = NOW()';
        $cmd .= 'WHERE pid = ? ';
        my $sth_update = $DBH_UPDATE->prepare($cmd);

        if ($CONF->debug) {
            dbg($cmd);
        }
        $sth_update->execute(
            $meta{'ring_datetime'},     $meta{'ring_size'},
            $meta{'ring_signature'},    $meta{'ring_camera'},
            $meta{'ring_shutterspeed'}, $meta{'ring_fstop'},
            $pid,
        );
    } else {
        my $picture_sequence = get_picture_sequence($meta{'datetime'});
        my $cmd              = "INSERT INTO pictures_information SET ";
        $cmd .= 'pid = ?, ';
        $cmd .= 'picture_lot = ?, ';
        $cmd .= 'camera_date = ?, ';
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
        $cmd .= 'date_last_maint = NOW(), ';
        $cmd .= 'date_added = NOW() ';
        my $sth_update = $DBH_UPDATE->prepare($cmd);

        if ($CONF->debug) {
            dbg($cmd);
        }
        $sth_update->execute(
            $pid,                         $meta{'picture_lot'},
            $meta{'ring_datetime'},       $meta{'ring_datetime'},
            $picture_sequence,            $meta{'source_path'},
            $meta{'source_file'},         $meta{'ring_size'},
            $meta{'ring_signature'},      $meta{'ring_camera'},
            $meta{'ring_shutterspeed'},   $meta{'ring_fstop'},
            $CONF->default_display_grade, $CONF->default_public,
        );
    }

    return;
}

# ------------------------------------------------------------------------
# Resize a picture, store some meta data, and return the resized
# picture

sub create_picture {
    my ($this_pid, $this_size_id, $this_picture, $this_file, $this_type) = @_;

    if ($this_pid == 0) {
        my $msg = "Invalid PID.  Skipping create_picture for $this_size_id";
        msg('error', $msg);
        return;
    }

    my $ts = sql_datetime();
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
        $table = $row->{picture_table};
    }
    if (!$table) {
        msg('fatal', "Invalid size id: $this_size_id");
    }

    my @blob;
    $blob[0] = $this_picture;
    my $this_pic = Image::Magick->New();
    $this_pic->BlobToImage(@blob);
    my $width       = $this_pic->Get('width');
    my $height      = $this_pic->Get('height');
    my $format      = $this_pic->Get('format');
    my $compression = $this_pic->Get('compression');
    my $signature   = $this_pic->Get('signature');

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
        if ($CONF->debug) {
            dbg("Producing picture $width by $height");
        }
        $newPic->Resize(width => $x, height => $y);
        my @bPic = $newPic->ImageToBlob();
        $ret_pic = $bPic[0];
    }
    my $ret_size = length($ret_pic);

    my $cmd = "INSERT INTO $table SET ";
    $cmd .= 'pid = ?, ';
    $cmd .= 'mime_type = ?, ';
    $cmd .= 'width = ?, ';
    $cmd .= 'height = ?, ';
    $cmd .= 'size = ?, ';
    $cmd .= 'signature = ?, ';
    $cmd .= 'format = ?, ';
    $cmd .= 'compression = ?, ';
    $cmd .= 'date_last_maint = NOW(), ';
    $cmd .= 'date_added = NOW() ';
    $cmd .= 'ON DUPLICATE KEY UPDATE ';
    $cmd .= 'mime_type = ?, ';
    $cmd .= 'width = ?, ';
    $cmd .= 'height = ?, ';
    $cmd .= 'size = ?, ';
    $cmd .= 'signature = ?, ';
    $cmd .= 'format = ?, ';
    $cmd .= 'compression = ?, ';
    $cmd .= 'date_last_maint = NOW() ';
    my $sth_update = $DBH_UPDATE->prepare($cmd);

    if ($CONF->debug) {
        dbg($cmd);
    }
    $sth_update->execute(
        $this_pid,  $this_type, $width,       $height,    $ret_size,
        $signature, $format,    $compression, $this_type, $width,
        $height,    $ret_size,  $signature,   $format,    $compression,
    );

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

    my $sel = 'SELECT * FROM picture_sizes WHERE size_id = ?';
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
# Construct a path

sub make_picture_path {
    my ($lot, $size_id, $pid, $type) = @_;

    if (!$lot) {
        my $m = "make_picture_path missing picture_lot ($lot)";
        msg('error', $m);
        return $m;
    }
    if (!$size_id) {
        my $m = "picture_path invalid size_id ($size_id)";
        msg('error', $m);
        return $m;
    }
    if ($pid < 1) {
        my $m = 'picture_path invalid pid';
        msg('error', $m);
        return $m;
    }
    if (!$type) {
        my $m = "picture_path invalid file_type ($type)";
        msg('error', $m);
        return $m;
    }

    my $pic_file = $CONF->picture_root;
    $pic_file .= '/' . $lot;
    $pic_file .= '/' . $size_id;
    $pic_file .= '/' . $pid;
    $pic_file .= '.' . $type;
    return $pic_file;
}

# ------------------------------------------------------------------------
# get picture sizes to generate

sub get_picture_sizes {
    my @flds   = ('max_height', 'max_width', 'picture_table', 'description');
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
# Save the processing error text

sub queue_error {
    my ($pid, $action, $msg) = @_;

    my $sel = 'INSERT INTO picture_action_queue ';
    $sel .= '(status, pid, action, error_text, date_last_maint, date_added) ';
    $sel .= "VALUES ('ERROR', ?, ?, ?, NOW(), NOW()) ";
    $sel .= 'ON DUPLICATE KEY UPDATE error_text = ?, ';
    $sel .= "status = 'ERROR', ";
    $sel .= 'date_last_maint = NOW() ';
    if ($CONF->debug) {
        dbg($sel);
    }

    my $sth = $DBH->prepare($sel);
    $sth->execute($pid, $action, $msg, $msg);
    return;
}

# ------------------------------------------------------------------------
# Set the picture queue status to pending

sub queue_action_set {
    my ($pid, $action) = @_;

    my $dt  = sql_datetime();
    my $sel = 'INSERT INTO picture_action_queue ';
    $sel .= '(pid, action, status, date_last_maint, date_added) ';
    $sel .= 'VALUES (?, ?, ?, ?, ?) ';
    $sel .= 'ON DUPLICATE KEY UPDATE status = ?, date_last_maint = ? ';
    if ($CONF->debug) {
        dbg($sel);
    }

    my $sth = $DBH->prepare($sel);
    $sth->execute($pid, $action, 'PENDING', $dt, $dt, 'PENDING', $dt);

    return;
}

# ------------------------------------------------------------------------
# Reset the picture queue status by deleting the entry in the table

sub queue_action_reset {
    my ($pid, $action) = @_;

    my $sel = 'DELETE FROM picture_action_queue ';
    $sel .= 'WHERE pid = ? ';
    $sel .= 'AND action = ? ';
    if ($CONF->debug) {
        dbg($sel);
    }

    my $sth = $DBH->prepare($sel);
    $sth->execute($pid, $action);

    return;
}

# ------------------------------------------------------------------------
# Queue a picture upload request

sub queue_upload {
    my ($path) = @_;

    my $dt  = sql_datetime();
    my $sel = 'INSERT INTO picture_upload_queue ';
    $sel .= '(path, status, date_last_maint, date_added) ';
    $sel .= 'VALUES (?, ?, ?, ?) ';
    $sel .= 'ON DUPLICATE KEY UPDATE status = ?, date_last_maint = ? ';
    if ($CONF->debug) {
        dbg($sel);
    }

    my $sth = $DBH->prepare($sel);
    $sth->execute($path, 'PENDING', $dt, $dt, 'PENDING', $dt);

    return;
}

# ------------------------------------------------------------------------
# Reset the picture upload queue status by deleting the entry in the table

sub queue_upload_reset {
    my ($path) = @_;

    my $sel = 'DELETE FROM picture_upload_queue ';
    $sel .= 'WHERE path = ? ';
    if ($CONF->debug) {
        dbg($sel);
    }

    my $sth = $DBH->prepare($sel);
    $sth->execute($path);

    return;
}

# ------------------------------------------------------------------------
# Save the upload processing error text

sub queue_upload_error {
    my ($path, $msg) = @_;

    my $sel = 'INSERT INTO picture_upload_queue ';
    $sel .= '(status, path, error_text, date_last_maint, date_added) ';
    $sel .= "VALUES ('ERROR', ?, ?, NOW(), NOW()) ";
    $sel .= 'ON DUPLICATE KEY UPDATE error_text = ?, ';
    $sel .= "status = 'ERROR', ";
    $sel .= 'date_last_maint = NOW() ';
    if ($CONF->debug) {
        dbg($sel);
    }

    my $sth = $DBH->prepare($sel);
    $sth->execute($path, $msg, $msg);
    return;
}

# ------------------------------------------------------------------------
# Make sure that the parameters passed to a routine are valid

sub validate_params {
    my ($name, $in_ref, $valid_ref) = @_;
    my %in        = %$in_ref;
    my @valid     = @$valid_ref;
    my %validList = ();
    for my $v (@valid) { $validList{$v}++; }
    for my $p (sort keys %in) {
        if (!$validList{$p}) {
            dbg("INVALID PARAMETER $p passed to $name");
        } else {
            dbg("$p = '$in{$p}' passed to $name");
        }
    }
    return;
}

# ------------------------------------------------------------------------
# Validate the mime type of a file and return the mime type and file type

sub validate_mime_type {
    my ($file_or_content) = @_;

    my $ft        = File::Type->new();
    my $mime_type = $ft->mime_type($file_or_content);

    my $sel = 'SELECT file_type FROM picture_types WHERE mime_type = ? ';
    if ($CONF->debug) {
        dbg($sel);
    }
    my $sth = $DBH->prepare($sel);
    $sth->execute($mime_type);

    my $file_type;
    if (my $row = $sth->fetchrow_hashref('NAME_lc')) {
        $file_type = $row->{file_type};
    }

    return $mime_type, $file_type;
}

END { }

1;

__END__

=head1 NAME

whm::rings - Utility routines for the rings gallery application

=head1 SYNOPSIS

=head1 AUTHOR

Bill MacAllister <bill@ca-zephyr.org>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2016, Bill MacAllister <bill@ca-zephyr.org>.

This code is free software; you can redistribute it and/or modify it
under the same terms as Perl. For more details, see the full
text of the at https://opensource.org/licenses/Artistic-2.0.

This program is distributed in the hope that it will be
useful, but without any warranty; without even the implied
warranty of merchantability or fitness for a particular purpose.

=cut
