# -------------------------------------------------------------------
# This module is used by the Rings gallery application.  See
# below for a more complete description.

package Rings::Common;

use AppConfig qw(:argcount :expand);
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
    our @EXPORT = qw($CONF
                     $DBH
                     $DBH_UPDATE
                     db_connect
                     db_disconnect
                     dbg
                     get_config
                     get_next_id
                     get_picture_sizes
                     msg
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

# ----------------------------------------------------------------------
# Open up connections to the MySQL data

sub db_connect {
    my $dbi = 'dbi:mysql:'
        . 'host='.$CONF->db_host.';'
        . 'database='.$CONF->db_name;
    my %attr = (PrintError => 1, RaiseError => 1);
    $DBH = DBI->connect ($dbi,
                         $CONF->db_user,
                         $CONF->db_password,
                         \%attr)
        or die "ERROR: Can't connect to database $dbi for read\n";
    $DBH_UPDATE = DBI->connect ($dbi,
                                $CONF->db_user,
                                $CONF->db_password,
                                \%attr)
        or die "ERROR: Can't connect to database $dbi for update\n";
    return;
}

# ----------------------------------------------------------------------
# disconnect from database

sub db_disconnect {
    $DBH->disconnect or die "ERROR: Database disconnect failed (read)";
    $DBH_UPDATE->disconnect or die "ERROR: Database disconnect failed (update)";
    return;
}

# ----------------------------------------------------------------------
# output debugging information

sub dbg {
    (my $tmp) = @_;
    msg('debug', $tmp);
    return;
}

# ----------------------------------------------------------------------
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
            DEFAULT => '/etc/rings/rings.conf',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'db_host=s',
        {
            DEFAULT => '/etc/rings/rings.conf',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define('db_name', { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define('debug', { ARGCOUNT => ARGCOUNT_ONE });
    $CONF->define(
        'default_group_id',
        {
            DEFAULT => 'new',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'default_display_size',
        {
            DEFAULT => 'larger',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'default_display_grade',
        {
            DEFAULT => 'A',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'default_display_seconds',
        {
            DEFAULT => '4',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'default_button_position',
        {
            DEFAULT => 'top',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'default_button_type=s',
        {
            DEFAULT => 'graphic',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'picture_root',
        {
            DEFAULT => '/srv/rings',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );
    $CONF->define(
        'syslog',
        {
            DEFAULT => 'local3',
            ARGCOUNT => ARGCOUNT_ONE,
        }
        );

    # Read preferences files in order from global location,
    # home directory, and command line.
    my @confs = ();
    push @confs, $global_config;
    push @confs, $conf_file;
    if ($ENV{'HOME'}) {
        push @confs, $ENV{'HOME'}.'/.rings.conf';
    }
    foreach my $z (@confs) {
        $CONF->file($z) if -e $z;
    }

    # Read db preferences if they exist
    if (-e $CONF->db_credentials) {
        my $db_conf = get_db_config($CONF->db_credentials);
        $CONF->db_user($db_conf->db_user);
        $CONF->db_password($db_conf->db_password);
    }

    if ($CONF->syslog) {
        openlog($CONF->syslog, 'pid', $CONF->syslog);
        if ($CONF->debug) {
            msg('info', 'logging to syslog ' . $CONF->syslog);
        }
    }

    return;
}

# ----------------------------------------------------------------------
# Read the db configuration file

sub get_db_config {
    my ($conf_file) = @_;
    my $db_conf = AppConfig->new({});
    $db_conf->define('db_user',     {ARGCOUNT => ARGCOUNT_ONE});
    $db_conf->define('db_password', {ARGCOUNT => ARGCOUNT_ONE});
    $db_conf->file($conf_file);
    return $db_conf;
}

# ----------------------------------------------------------------------
# get next id

sub get_next_id {

    (my $id) = @_;

    my $return_number = "NEXT-NUMBER-FAILED";

    my $sel = 'SELECT next_number FROM next_number WHERE id=? ';
    dbg ($sel) if $CONF->debug;
    my $sth = $DBH->prepare ($sel);
    $sth->execute($id);

    my $cnt = 0;
    while (my $row = $sth->fetchrow_hashref('NAME_lc') ) {
        $return_number = $row->{next_number} + 1;
        my $cmd = 'UPDATE next_number SET next_number=? WHERE id=? ';
        dbg ($cmd) if $CONF->debug;
        my $sth_update = $DBH_UPDATE->prepare ($cmd);
        $sth_update->execute($return_number, $id)
            or die "Error updating next number for $id: $DBH::errstr\n";
        $cnt++;
    }
    if ($cnt == 0) {
        $return_number = 1;
        my $cmd = 'INSERT INTO  next_number (id,next_number) VALUES (?,?) ';
        dbg ($cmd) if $CONF->debug;
        if ($CONF->update) {
            my $sth_update = $DBH_UPDATE->prepare ($cmd);
            $sth_update->execute($id, $return_number);
        }
    }

    return $return_number;

}

# ----------------------------------------------------------------------
# Generate a useful message

sub msg {
    my ($severity, $msg) = @_;
    my @lines = split /\n/, $msg;
    foreach my $l (@lines) {
        if ($CONF->syslog) {
            syslog('info', uc($severity)." $l");
        } else {
            dbg(uc($severity). " $l");
        }
    }
    die $msg if $severity eq 'fatal';
}

# ----------------------------------------------------------------------
# sql date time string from unix time stamp

sub sql_datetime {

    my ($dt) = @_;

    if (!$dt) {
        $dt = time
    }
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($dt);
    $mon++;
    $year += 1900;

    return sprintf("%04d-%02d-%02d %02d:%02d:%02d",
                   $year,$mon,$mday,$hour,$min,$sec);
}

# ----------------------------------------------------------------------
# store pictures

sub store_meta_data {

    my ($in_file, $pid) = @_;

    dbg (" Processing $in_file");
    if (! -e $in_file) {
        msg("ERROR: $in_file not found");
        return;
    }

    # Set file paths and names
    my $source_path = File::Spec->rel2abs( $in_file ) ;
    my ($source_file, $source_dirs, $source_suffix) = fileparse($source_file);
    my @dirs = split(/\//, $source_dirs);
    my $picture_lot = @dirs[-1];

    # Read in the file
    my $this_picture = read_file("$this_path/$this_file", binmode => ':raw');

    # Get picture meta data
    my @blob;
    $blob[0] = $this_picture;
    my $pic = Image::Magick->New();
    $pic->BlobToImage(@blob);

    my ($width,
        $height,
        $size,
        $format,
        $compression,
        $signature)
        = $thisPic->Get('width',
                        'height',
                        'filesize',
                        'format',
                        'compression',
                        'signature');
    if ($CONF->debug) {
        dbg ("      format: $format");
        dbg (" compression: $compression");
        dbg ("       width: $width");
        dbg ("      height: $height");
    }

    my $info = ImageInfo(\@blob[0]);
    my $camera            = ${$info}{'Model'};
    my $this_datetime     = ${$info}{'CreateDate'};
    my $this_shutterspeed = ${$info}{'ShutterSpeed'};
    my $this_fnumber      = ${$info}{'FNumber'};
    my $this_raw_size     = length $blob[0];
    if ($CONF->debug) {
        dbg ("        size: $this_raw_size");
        dbg ("       model: $camera");
        dbg ("    datetime: $this_datetime");
        dbg ("exposuretime: $this_shutterspeed");
        dbg ("     fnumber: $this_fnumber");
        dbg ("EXIF Information ==============================");
        foreach my $t (keys %{$info}) {
            print "$t = ${$info}{$t}\n";
        }
        dbg ("EXIF Information end ==========================");
    }
    if (!$this_datetime) {
        $this_datetime = sql_datetime();
    }

    # Store meta data
    if ($pid) {
        my $sel = "SELECT pid FROM pictures_prints WHERE pid=? ";
        my $sth = $DBH->prepare ($sel);
        if ($CONF->debug) {dbg($sel);}
        $sth->execute($thisPID);
        my $row_found;
        while (my $row = $sth->fetchrow_hashref) {$row_found = 1;}
        if (!$row_found) {
            msg("ERROR: $pid not found when attempting meta data update");
            return;
        }
        my $cmd = "UPDATE pictures_information SET ";
        $cmd .= 'source_file = ?,';
        $cmd .= 'picture_lot = ?,';
        $cmd .= 'file_name = ?,';
        $cmd .= 'camera = ?,';
        $cmd .= 'shutter_speed = ?,';
        $cmd .= 'fstop = ?,';
        $cmd .= 'date_last_maint = ?,';
        $cmd .= 'WHERE pid = ? ';
        my $sth_update = $DBH_UPDATE->prepare ($cmd);
        if ($CONF->debug) {dbg($cmd);}
        if ($CONF->update) {
            $sth_update->execute($source_file,
                                 $picture_lot,
                                 $file_name,
                                 $camera,
                                 $this_shutter_speed,
                                 $this_fstop,
                                 sql_datetime(),
                                 $pid
                );
        }
        return $pid;
    }

    # Set the picture sequence
    # TODO

    $pid = get_next_id("pid");
    my $cmd = 'INSERT INTO pictures_information SET ';
    $cmd .= 'pid = ?,';
    $cmd .= 'source_file = ?,';
    $cmd .= 'picture_lot = ?,';
    $cmd .= 'file_name = ?,';
    $cmd .= 'date_taken = ?,';
    $cmd .= 'picture_date = ?,';
    $cmd .= 'picture_sequence = ?,';
    $cmd .= 'grade = ?,';
    $cmd .= 'public = ?,';
    $cmd .= 'camera = ?,';
    $cmd .= 'shutter_speed = ?,';
    $cmd .= 'fstop = ?,';
    $cmd .= 'date_last_maint = ?,';
    $cmd .= 'date_added = ? ';
    my $sth_update = $DBH_UPDATE->prepare ($cmd);
    if ($CONF->debug) {dbg($cmd);}
    if ($CONF->update) {
        $sth_update->execute($pid,
                             $source_path,
                             $picture_lot,
                             $source_file,
                             $this_date,
                             $this_date,
                             $picture_sequence,
                             $CONF->default_grade,
                             $CONF->default_public,
                             $this_camera,
                             $this_shutter_speed,
                             $this_fstop,
                             sql_datetime(),
                             sql_datetime()
            );
    }

    return $pid;
}

# ----------------------------------------------------------------------
# trim leading and trailing white space

sub trim {
    my ($out) = @_;
    $out =~ s/^\s+//;
    $out =~ s/\s+$//;
    return $out;
}

# ----------------------------------------------------------------------
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

# ----------------------------------------------------------------------
# get picture sizes to generate

sub get_picture_sizes {
    my $sel = 'SELECT * FROM picture_sizes';
    my $sth = $DBH->prepare ($sel);
    my %psizes = ();
    if ($CONF->debug) {dbg($sel);}
    $sth->execute();
    while (my $row = $sth->fetchrow_hashref) {
        $psizes{$row->{max_size}} = $row->{description};
    }
    return %psizes;
}

# ----------------------------------------------------------------------
# Make sure that the parameters passed to a routine are valid

sub validate_params {
    my ($name, $in_ref, $valid_ref) = @_;
    my %in = %$in_ref;
    my @valid = @$valid_ref;
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

END {}

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
