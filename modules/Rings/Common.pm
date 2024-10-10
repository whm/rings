# -------------------------------------------------------------------
# Copyright (c) 2016-2024, Bill MacAllister <bill@ca-zephyr.org>
# File: Common.pm
# Description: This module is used by the Rings gallery application.

package Rings::Common;

use AppConfig qw(:argcount :expand);
use Carp;
use Compress::Zlib;
use DBI;
use File::Basename;
use File::Slurp;
use File::Spec;
use File::Type;
use Getopt::Long;
use Image::ExifTool 'ImageInfo';
use Image::Magick;
use MIME::Base64;
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
      file_signature
      get_config
      get_id_list
      get_meta_data
      get_next_id
      get_picture_sizes
      get_picture_types
      make_picture_path
      msg
      normalize_picture_lot
      pid_to_path
      queue_error
      queue_action_reset
      queue_action_set
      queue_upload
      queue_upload_error
      queue_upload_reset
      set_new_picture
      sql_datetime
      sql_die
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
    $DBH = DBI->connect($dbi, $CONF->db_user, $CONF->db_password)
      or die "ERROR: Can't connect to database $dbi for read\n";
    $DBH_UPDATE = DBI->connect($dbi, $CONF->db_user, $CONF->db_password)
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

    # Define the properties
    $CONF = AppConfig->new({});

    # Define the properties
    $CONF->define(
        'db_secret=s',
        {
            DEFAULT  => '/etc/rings/rings_db.conf',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'db_host=s',
        {
            DEFAULT  => 'db.com',
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
        'krb_cache',
        {
            DEFAULT  => 'FILE:/run/rings.tgt',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define('krb_keytab', { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define(
        'krb_principal',
        {
            DEFAULT  => '-U',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'krb_realm',
        {
            DEFAULT  => 'CA-ZEPHYR.ORG',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'ldap_host',
        {
            DEFAULT  => 'cz-ldap-replica-1.ca-zephyr.org',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'ldap_base',
        {
            DEFAULT  => 'ou=people,dc=ca-zephyr,dc=org',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'ldap_admin_attr',
        {
            DEFAULT  => 'czPrivilegeGroup',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'ldap_admin_val',
        {
            DEFAULT  => ['ring:admin'],
            ARGCOUNT => ARGCOUNT_LIST,
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
        'picture_input_root',
        {
            DEFAULT  => '/srv/rings-input',
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
        'ring_admin',
        {
            DEFAULT  => 'Mrochek Freed',
            ARGCOUNT => ARGCOUNT_ONE,
        }
    );
    $CONF->define(
        'ring_admin_princ',
        {
            DEFAULT  => 'user@REALM',
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

    # Read configuration from the file
    $CONF->file($conf_file);

    # Read db preferences if they exist
    if (-e $CONF->db_secret) {
        $CONF->define('db_user',     { ARGCOUNT => ARGCOUNT_ONE });
        $CONF->define('db_password', { ARGCOUNT => ARGCOUNT_ONE });
        my $db_conf = get_db_config($CONF->db_secret);
        $CONF->db_user($db_conf->db_user);
        $CONF->db_password($db_conf->db_password);
    } else {
        msg(
            'fatal',
            'db_secret file not found (' . $CONF->db_secret . ')'
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
# read the rings configuration directory and return a hash of the
# configuration IDs.  The key to the has is the ID and the value is
# the path to the configuration file.

sub get_id_list {
    my $ring_dir = '/etc/rings';
    my %id_list  = ();
    opendir(my $dh, $ring_dir) || die "Can't open $ring_dir: $!";
    while (readdir $dh) {
        my $f  = $_;
        my $id = $f;
        if ($id =~ s/[.]conf$//xms) {
            if ($id !~ /db$/xms) {
                $id_list{$id} = "$ring_dir/$f";
            }
        }
    }
    closedir $dh;
    return %id_list;
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
    if ($sth->err) {
        print("INFO: id = $id");
        sql_die($sel, $sth->err, $sth->errstr);
    }

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
        my $cmd = 'INSERT INTO next_number (id,next_number) VALUES (?,?) ';
        dbg($cmd) if $CONF->debug;
        my $sth_update = $DBH_UPDATE->prepare($cmd);
        $sth_update->execute($id, $return_number);
        if ($sth_update->err) {
            print("INFO: id = $id, next_number = $return_number");
            sql_die($cmd, $sth_update->err, $sth_update->errstr);
        }
    }

    return $return_number;

}

# ------------------------------------------------------------------------
# set picture in the new picture group

sub set_new_picture {

    (my $pid) = @_;

    my $sel
      = 'INSERT INTO picture_rings SET '
      . "uid = '"
      . $CONF->default_group_id . "', "
      . 'pid = ?, '
      . 'date_last_maint = NOW(), '
      . 'date_added = NOW() '
      . 'ON DUPLICATE KEY UPDATE '
      . 'date_last_maint = NOW() ';
    dbg($sel) if $CONF->debug;
    my $sth_update = $DBH_UPDATE->prepare($sel);
    $sth_update->execute($pid)
      or die "Error updating picture_rings for $pid: $DBH::errstr\n";
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
# Take a file name, read the first n bytes, base64 encode the result and
# return it.

sub file_signature {
    my ($in_file, $in_limit) = @_;
    my $this_size  = -s $in_file;
    my $this_limit = $in_limit;
    if ($this_limit > $this_size) {
        $this_limit = $this_size;
    }
    my $head;
    open(my $fd, '<', $in_file);
    read($fd, $head, $this_limit);
    close $fd;
    my $small = compress($head);
    my $b64   = encode_base64($small, '');
    return $b64;
}

# ------------------------------------------------------------------------
# Get meta data from picture and return a hash with the data.

sub get_meta_data {

    my ($in_file) = @_;

    # Data returned will be passed in a hash
    my %ret = ();
    $ret{'ring_path'} = $in_file;

    # Create a new Image::ExifTool object
    my $exifTool = Image::ExifTool->new;

    # Extract meta information from an image
    my %options = (
        'FastScan'  => 1,
        'PrintConv' => 1,
    );
    $exifTool->ExtractInfo($in_file, \%options);

    # Get list of tags in the order they were found in the file
    my @tagList = $exifTool->GetFoundTags('File');

    my %info = ();
    for my $tag (@tagList) {
        my $value  = $exifTool->GetValue($tag);
        my $lc_tag = lc($tag);
        $lc_tag =~ s/\s+//xmsg;
        $info{$lc_tag} = $value;
        $ret{$lc_tag}  = $value;
        if ($CONF->debug) {
            dbg("$lc_tag = $value\n");
        }
    }

    # Make sure this data is pulled from the image
    $ret{'ring_width'}        = $info{'imagewidth'};
    $ret{'ring_height'}       = $info{'imageheight'};
    $ret{'ring_size'}         = -s $in_file;
    $ret{'ring_compression'}  = $info{'filetype'};
    $ret{'ring_filename'}     = $info{'filename'};
    $ret{'ring_filetype'}     = $info{'filetype'};
    $ret{'ring_signature'}    = file_signature($in_file, 1024);
    $ret{'ring_shutterspeed'} = $info{'shutterspeed'};
    $ret{'ring_fstop'}        = $info{'fnumber'};
    $ret{'ring_mime_type'}    = $info{'mimetype'};

    # Look for some fields under multiple names
    $ret{'ring_format'} = 'UNKNOWN';
    my @format_names = ('format', 'filetype');
    for my $c (@format_names) {
        if ($info{$c}) {
            $ret{'ring_format'} = $info{$c};
            last;
        }
    }

    $ret{'ring_camera'} = 'UNKNOWN';
    my @camera_names = ('make', 'model');
    for my $c (@camera_names) {
        if ($info{$c}) {
            $ret{'ring_camera'} = $info{$c};
        }
    }

    $ret{'ring_datetime'} = '1948-09-25 09:00:00';
    my @date_names = (
        'datemodify', 'modifydate', 'datetimeoriginal',
        'datecreate', 'createdate'
    );
    for my $n (@date_names) {
        if ($info{$n}) {
            $ret{'ring_datetime'} = sql_format_datetime($info{$n});
            if ($CONF->debug) {
                dbg("n = $n, ring_datetime = $ret{'ring_datetime'}");
            }
            last;
        }
    }

    if ($CONF->debug) {
        for my $a (sort keys %ret) {
            dbg('ret{' . $a . "} = $ret{$a}");
        }
    }

    return %ret;
}

# ------------------------------------------------------------------------
# Store meta data for a picture

sub store_meta_data {

    my ($pid, $meta_data_ref) = @_;
    my %meta = %{$meta_data_ref};
    my $ts   = sql_datetime();

    if ($CONF->debug) {
        dbg("Storing meta data for $pid");
        for my $a (sort keys %meta) {
            dbg("meta{$a} = $meta{$a}");
        }
    }

    # Set file paths and names
    $meta{'source_path'} = $meta{'ring_path'};
    ($meta{'source_file'}, $meta{'source_dirs'}, $meta{'source_suffix'})
      = fileparse($meta{'source_path'});
    my @dirs = split(/\//, $meta{'source_dirs'});
    $meta{'picture_lot'} = normalize_picture_lot(@dirs[-1]);

    # Store summary meta data
    my $sel = "SELECT * FROM pictures_information WHERE pid=? ";
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($pid);
    if ($sth->err) {
        print("INFO: pid = $pid");
        sql_die($sel, $sth->err, $sth->errstr);
    }

    if (my $row = $sth->fetchrow_hashref) {
        my $source_file = $row->{source_file};
        if (!$row->{source_file}) {
            $source_file = $meta{'source_path'};
        }
        my $file_name = $row->{file_name};
        if (!$row->{file_name}) {
            $file_name = $meta{'source_file'};
        }
        my $cmd = "UPDATE pictures_information SET ";
        $cmd .= 'camera_date = ?, ';
        $cmd .= 'raw_picture_size = ?, ';
        $cmd .= 'raw_signature = ?, ';
        $cmd .= 'camera = ?, ';
        $cmd .= 'shutter_speed = ?, ';
        $cmd .= 'fstop = ?, ';
        $cmd .= 'source_file = ?, ';
        $cmd .= 'file_name = ?, ';
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
            $source_file,               $file_name,
            $pid,
        );
        if ($sth_update->err) {
            print("INFO: pid = $pid");
            sql_die($cmd, $sth_update->err, $sth_update->errstr);
        }
    } else {
        my $cmd = "INSERT INTO pictures_information SET ";
        $cmd .= 'pid = ?, ';
        $cmd .= 'picture_lot = ?, ';
        $cmd .= 'camera_date = ?, ';
        $cmd .= 'picture_date = ?, ';
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
            $pid,
            $meta{'picture_lot'},
            $meta{'ring_datetime'},
            $meta{'ring_datetime'},
            $meta{'source_path'},
            $meta{'source_file'},
            $meta{'ring_size'},
            $meta{'ring_signature'},
            $meta{'ring_camera'},
            $meta{'ring_shutterspeed'},
            $meta{'ring_fstop'},
            $CONF->default_display_grade,
            $CONF->default_public,
        );
        if ($sth_update->err) {
            msg('info', "pid = $pid");
            sql_die($cmd, $sth_update->err, $sth_update->errstr);
        }
    }

    return;
}

# ------------------------------------------------------------------------
# Resize a picture, store some meta data, and return the resized
# picture

sub create_picture {
    my ($this_pid, $this_size_id, $new_path, $pic_ref) = @_;
    my %pic = %{$pic_ref};

    if ($CONF->debug) {
        my $m = 'create_picture';
        $m .= " this_pid=$this_pid";
        $m .= " this_size_id=$this_size_id";
        $m .= " new_path=$new_path";
        dbg($m);
        for my $a (sort keys %pic) {
            dbg('pic{' . $a . "} = $pic{$a}");
        }
    }

    if ($this_pid == 0) {
        my $msg = "Invalid PID.  Skipping create_picture for $this_size_id";
        msg('error', $msg);
        return;
    }

    my $ts = sql_datetime();
    my $max_x;
    my $max_y;
    my $sel = 'SELECT * FROM picture_sizes WHERE size_id = ?';
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($this_size_id);
    if ($sth->err) {
        print("INFO: size_id = $this_size_id");
        sql_die($sel, $sth->err, $sth->errstr);
    }
    if (my $row = $sth->fetchrow_hashref) {
        $max_y = $row->{max_height};
        $max_x = $row->{max_width};
    }

    my $width     = $pic{'ring_width'};
    my $height    = $pic{'ring_height'};
    my $filename  = $pic{'ring_filename'};
    my $format    = $pic{'ring_format'};
    my $signature = $pic{'ring_signature'};
    my $mime_type = $pic{'ring_mime_type'};

    # Resize picture if requested
    my $new_pic = Image::Magick->New();
    $new_pic->Read($pic{'ring_path'});
    if ($max_x != 0 && $max_y != 0) {
        my $x  = $width;
        my $y  = $height;
        my $x1 = $max_x;
        my $y1 = int(($x1 / $width) * $height);
        my $y2 = $max_y;
        my $x2 = int(($y2 / $height) * $width);

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
            dbg("Producing picture $width by $height at $new_path");
        }
        $new_pic->Resize(width => $x, height => $y);
    }
    my $image_cnt = $new_pic->Write($new_path);
    my $new_size  = -s $new_path;
    if ($CONF->debug) {
        dbg("image_cnt = $image_cnt");
        dbg("new_size = $new_size");
    }

    my $cmd = 'INSERT INTO picture_details SET ';
    $cmd .= 'size_id = ?, ';
    $cmd .= 'pid = ?, ';
    $cmd .= 'filename = ?, ';
    $cmd .= 'mime_type = ?, ';
    $cmd .= 'width = ?, ';
    $cmd .= 'height = ?, ';
    $cmd .= 'size = ?, ';
    $cmd .= 'format = ?, ';
    $cmd .= 'signature = ?, ';
    $cmd .= 'date_last_maint = NOW(), ';
    $cmd .= 'date_added = NOW() ';
    $cmd .= 'ON DUPLICATE KEY UPDATE ';
    $cmd .= 'mime_type = ?, ';
    $cmd .= 'width = ?, ';
    $cmd .= 'height = ?, ';
    $cmd .= 'size = ?, ';
    $cmd .= 'signature = ?, ';
    $cmd .= 'format = ?, ';
    $cmd .= 'date_last_maint = NOW() ';
    my $sth_update = $DBH_UPDATE->prepare($cmd);

    if ($CONF->debug) {
        dbg($cmd);
    }
    $sth_update->execute(
        $this_size_id, $this_pid, $filename, $mime_type, $width,
        $height,       $new_size, $format,   $signature, $mime_type,
        $width,        $height,   $new_size, $signature, $format,
    );
    if ($sth_update->err) {
        print("INFO: pid = $this_pid");
        sql_die($cmd, $sth_update->err, $sth_update->errstr);
    }

    return;
}

# ------------------------------------------------------------------------
# Print sql error message and die

sub sql_die {
    my ($sql, $errno, $err) = @_;
    print("INFO: problem sql: $sql\n");
    print("ERROR: $errno - $err\n");
    die "FATAL: SQL error\n";
    return;
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
# Create a directory

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
# Create directories needed to store a picture

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
# valididate the picure size

sub check_picture_size {
    my ($this_id) = @_;

    my $sel = 'SELECT * FROM picture_sizes WHERE size_id = ?';
    my $sth = $DBH->prepare($sel);
    if ($CONF->debug) {
        dbg($sel);
    }
    $sth->execute($this_id);
    if ($sth->err) {
        print("INFO: size_id = $this_id");
        sql_die($sel, $sth->err, $sth->errstr);
    }
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
        my $m = 'picture_path invalid pid ($pid)';
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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }
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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }
    while (my $row = $sth->fetchrow_hashref) {
        $mime_types{ $row->{mime_type} } = $row->{file_type};
    }
    return %mime_types;
}

# ------------------------------------------------------------------------
# Normalize the picture lot

sub normalize_picture_lot {
    my ($dir) = @_;
    my $lot = lc($dir);
    $lot =~ s/\s+//xmsg;
    return $lot;
}

# ------------------------------------------------------------------------
# Return the path to a file given the pid, group, size, and type desired

sub pid_to_path {
    my ($pid, $group, $size_id, $type) = @_;

    if (!check_picture_size($size_id)) {
        msg('fatal', "Invalid size: $size_id");
    }

    $type =~ s/[.]//xmsg;

    my $path = $CONF->picture_root;
    $path .= "/${group}/${size_id}/${pid}.${type}";
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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }
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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }

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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }

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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }

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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }

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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }
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
    if ($sth->err) {
        sql_die($sel, $sth->err, $sth->errstr);
    }

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

Rings::Commons - Utility routines for the rings gallery application

=head1 EXPORTS

=over 4

=item $CONF

The configuration read from the configuration file.  See CONFIGURATION
PARAMETERS below for a list of possible settings.  This is the return
from the get_config routine.

=item $DBH

The data base handle to use for reading the Rings database.  The
handle is set by the db_connect routine.

=item $DBH_UPDATE

The data base handle to use for updating the Rings database.  The
handle is set by the db_connect routine.

=item db_connect

Connect to the Rings database and set the $DBH and $DBH_UPDATE
database handles.

=item db_disconnect

Disconnect from the Rings database.

=item dbg

Routine to display debugging information.

=item check_picture_size

Perform a search of the Rings table picture_sizes to validate
if the request size is supported.  The routine croaks if the
size is not supported.

=item create_picture_dirs

Create the directory to support a new picture upload.  The
tree created is of the form:

    picture_root/group/size

=item create_picture

Resize a raw picture, store some meta data, and return the resized
picture.

=item get_config

Read the configuration file.  The routine accepts on parameter, the
path to the file to read.  The default file is /etc/rings/rings.conf.
See CONFIGURATION PARAMETERS below for a list of possible settings.

=item get_meta_data

Get the meta data from a picture file using Image::Magick and
return a hash with the data.

=item get_next_id

Get the next unused picture ID.

=item get_picture_sizes

Return a hash of pictures sizes to generate from the entries in
the picture_sizes table. Return a hash of the containing the
max_height, max_width, picture_table, and description for each
size.

=item get_picture_types

Return a hash of valid picture types and their associated MIME
type.

=item make_picture_path

Give the picture lot, size ID, picture ID, and picture type
return a path to be use to store the picture.

The path is of the form:

    picture_root/lot/size_id/pid.type

=item msg

Display processing messages.  The routine accepts two parameters:
a severity and the text to be displayed.  Messages are written
to STDOUT and if syslog is configured messages are written to
syslog.  If a message severify is 'fatal' the routine invokes
croak with the message text;

=item normalize_picture_lot

Return the input sting after removing all white space and
lowercase'ing the string.

=item pid_to_path

Given a picture ID, the group ID, the size_id, and the type
return the path to the picture file.

=item queue_error

Save processing errors in the picture_action_queue table.

=item queue_action_reset

Delete a queue entry from the picture_action_queue table.

=item queue_action_set

Set a picture's processing status to PENDING in the
picture_action_queue table.

=item queue_upload

Insert a processing request in the picture_upload_queue table.

=item queue_upload_error

Write processing errors in the picture_upload_queue table.

=item queue_upload_reset

Delete a row from the picture_upload_queue table.

=item set_new_picture

Add a picture to the 'new' picture group.

=item sql_datetime

Generate an SQL datetime string from a UNIX time stamp.

=item sql_die

Generate an SQL error message and exit.

=item sql_format_datetime

Generate a valid SQL datatime string from a datetime of the
form yyyymmddhhmmss or yyyymmdd.

=item store_meta_data

Sort the meta data for a picture in the Rings database.

=item trim

Trival routine to string leading and trailing white space.

=item unix_seconds

Return a UNIX timestamp from an SQL datetime string.

=item validate_mime_type

Validate the mime type of a file and return the mime type and file
type.

=back

=head1 CONFIGURATION PARAMETERS

=over 4

=item db_credentials

The path to a file containing the database username and password
to use when connection to the Rings database.  The default value
is /etc/rings/rings.conf'.

=item db_host

The hostname of the Rings database server.

=item db_name

The name of the Rings database.

=item debug

Turn debugging messages.

=item default_group_id

The default group id for new pictures.  The default is 'new'.

=item default_display_size

The default display size if the user does not override the
picture display size.  The default value is 'larger'.

=item default_display_grade

The default picture grade to display.  The default is 'A'.

=item default_display_seconds

The default pause to use when automatically display the pictures
for a given person.  The default is 4 seconds.

=item default_button_position

The default position for picture links.  The default is 'top'.

=item default_button_type

The default button type is use.  The default is 'graphic'.

=item default_public

The default for public accessibility for a picture.  The default is
'Y'.

=item krb_keytab

The keytab to use for authenticated LDAP searches.  The default is
/etc/krb5.keytab.

=item krb_principal

The Kerberos principal to use for authenticated LDAP searches.  The
default is -U.

=item ldap_host

The LDAP host search when performing authorization searches.  The
default is 'cz-ldap-replica-1.ca-zephyr.org'.

=item ldap_base

The base dn for authorization LDAP searches.  The default is
'ou=people,dc=ca-zephyr,dc=org'.

=item ldap_admin_attr

The attribute to search for when determining admin authorization.
The default is 'czPrivilegeGroup'.

=item ldap_admin_val

The values of ldap_admin_attr to be consider as authorizing administrative
access to the rings.  This property can be specified multiple times.
The default value is 'ring:admin'.

=item loop_limit_daemon

The loop limit using by Rings daemons.  The default is 120.

=item loop_limit_load

The number of pictures to load in a single processing loop.  The
default value is 120.

=item picture_root

The root directory where pictures are stored.

=item queue_sleep

The number of seconds to sleep beteen processing cycles.  The default
is 30.  If processing is terminate by reaching the loop_load_limit
then this value is set to zero for the next iteration.

=item syslog

If defined specifies that processing messages are to be written to
syslog.  The default is 'local3'.

=item display_size

The display size used by the PHP interface.

=item index_size

The picture size used in indexes in the PHP interface.

=item maint_size

The picture size used in the PHP maintenance script.

=item db_user

The Rings database user.  This value must be specified in the
db_credentials file.

=item db_password

The Rings password for the database user.  This value must be
specified in the db_credentials file.

=back

=head1 AUTHOR

Bill MacAllister <bill@ca-zephyr.org>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2016-2024, Bill MacAllister <bill@ca-zephyr.org>.

This code is free software; you can redistribute it and/or modify it
under the same terms as Perl. For more details, see the full
text of the at https://opensource.org/licenses/Artistic-2.0.

This program is distributed in the hope that it will be
useful, but without any warranty; without even the implied
warranty of merchantability or fitness for a particular purpose.

=cut
