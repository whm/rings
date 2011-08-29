#!/usr/bin/perl

use strict;
use Cwd;
use DBI;
use Getopt::Long;
use Pod::Usage;
use Time::Local;

use vars qw (
	     %props
	     $dbh
	     $debug
	     $debug_time
	     $opt_debug
	     $opt_help
	     $opt_manual
	     $opt_properties
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
# test boolean

sub test_boolean {
                 
    my ($in) = @_;
    $in =~ s/\s+//g;
    $in =~ lc($in);

    my $result = 0;

    if ($in =~ /^(\d+)$/) {
	if ($1 != 0) {$result = 1;}
    } elsif ($in =~ /^true$/) {
	$result = 1;
    } elsif ($in =~ /^yes$/) {
	$result = 1;
    }

    return $result;

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
                  
# ------------------------------------------------
# write a file to disk

sub write_file {

    my ($f, $c) = @_;
    if ($opt_debug) {debug_output ("writing file:$f");}
    open (outfile, ">$f");
    print outfile $c;
    close outfile;
}

# ------------------------------------------------
# write html to display the image

sub write_html {

    my ($last_f, $this_f, $this_i, $next_f,
	$lfile, $ltitle, $rfile, $rtitle,
	$desc, $pid) = @_;
    
    # -- get list of people, places
    my $sel = "SELECT ";
    $sel .= "p.display_name ";
    $sel .= "FROM picture_details d ";
    $sel .= "JOIN people_or_places p ";
    $sel .= "ON (d.uid = p.uid) ";
    $sel .= "WHERE d.pid='$pid' ";
    $sel .= "AND p.display_name NOT LIKE 'ZID%' ";
    $sel .= "ORDER BY p.display_name ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();
    my @names;
    my $name_cnt = 0;
    while (my $row = $sth->fetchrow_hashref) {
	push @names, $row->{display_name};
	$name_cnt++;
    }

    # -- Create HTML
    open (f, ">$this_f");
    print f "<html>\n";
    print f "<head>\n";
    print f "<title>Picture Show</title>\n";
    print f "</head>\n";
    print f "\n";
    print f "<body bgcolor=\"#000000\">\n";
    print f "\n";
    print f "<div align=\"center\">\n";
    print f "<table border=\"0\">\n";

    # -- next-previous buttons
    print f "<tr>\n";
    print f " <td>";
    if (length($last_f) == 0) {
	print f "&nbsp;";
    } else {
	print f "<a href=\"$last_f\">";
	print f "<font color=\"#ffffff\" ";
	print f "\"face=\"Arial, Helvetica, sans-serif\"><b>";
	print f "Previous Image";
	print f "</b></font>";
	print f "</a>";
    }
    print f " </td>\n";

    print f " <td align=\"right\">";
    if (length($next_f) == 0) {
	print f "&nbsp;";
    } else {
	print f "  <a href=\"$next_f\">";
	print f "<font color=\"#ffffff\" ";
	print f "\"face=\"Arial, Helvetica, sans-serif\"><b>";
	print f "Next Image";
	print f "</b></font>";
	print f "</a>";
    }
    print f " </td>\n";
    print f "</tr>\n";

    # -- the picture
    print f "<tr>\n";
    print f " <td colspan=\"2\"><img src=\"$this_i\"></td>\n";
    print f "</tr>\n";

    # -- the description
    if (length($desc) > 0) {
	print f "<tr>\n";
	print f " <td colspan=\"2\" align=\"center\" width=\"600\">";
	print f "<font color=\"#ffffff\" ";
	print f "\"face=\"Arial, Helvetica, sans-serif\"><b>";
	print f $desc;
	print f "</b></font>";
	print f "</td>\n";
	print f "</tr>\n";
    }

    # -- the names
    if ($name_cnt > 0) {
	print f "<tr>\n";
	print f " <td colspan=\"2\" align=\"center\" width=\"600\">";
	print f "<font color=\"#ffffff\" ";
	print f "\"face=\"Arial, Helvetica, sans-serif\"><b>";
	foreach my $n (@names) {
	    print f "$n&nbsp;&nbsp;";
	}
	print f "</b></font>";
	print f "</td>\n";
	print f "</tr>\n";
    }

    # -- image size stuff
    print f "<tr>\n";
    print f " <td>";
    print f "<a href=\"$lfile\">";
    print f "<font color=\"#ffffff\" ";
    print f "\"face=\"Arial, Helvetica, sans-serif\"><b>";
    print f $ltitle;
    print f "</b></font>";
    print f "</a>";
    print f " </td>\n";
    print f " <td align=\"right\">";
    print f "  <a href=\"$rfile\">";
    print f "<font color=\"#ffffff\" ";
    print f "\"face=\"Arial, Helvetica, sans-serif\"><b>";
    print f $rtitle;
    print f "</b></font>";
    print f "</a>";
    print f " </td>\n";
    print f "</tr>\n";

    # -- the index
    print f "<tr>\n";
    print f " <td colspan=\"2\" align=\"center\">";
    print f"<a href=\"index.html\">";
    print f "<font color=\"#ffffff\" ";
    print f "\"face=\"Arial, Helvetica, sans-serif\"><b>";
    print f "Index";
    print f "</b></font>";
    print f "</a>\n";
    print f " </td>\n";
    print f "</tr>\n";

    print f "\n";
    print f "</table>\n";
    print f "</div>\n";
    print f "\n";

    print f "</body>\n";
    print f "</html>\n";

    close f;

}

# ------------------------------------------------
# process the files

sub read_and_write {

    # -- get a list of picture ids

    my $sel = "CREATE TEMPORARY TABLE tmp_picture_list ";
    $sel .= "SELECT DISTINCT d.pid ";
    $sel .= "FROM picture_details d ";
    $sel .= "JOIN pictures_information p ";
    $sel .= "ON d.pid=p.pid ";
    $sel .= "WHERE ".$props{'selection'}." ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();
    $sth->finish();

    # -- Loop through the pictures

    my $sel = "SELECT ";
    $sel .= "pic.date_taken date_taken, ";
    $sel .= "pic.description description, ";
    $sel .= "pic.pid pid ";
    $sel .= "FROM  pictures_information pic ";
    $sel .= "JOIN tmp_picture_list tmp ";
    $sel .= "ON (tmp.pid = pic.pid) ";
    $sel .= "ORDER BY pic.date_taken ";
    my $sth = $dbh->prepare ($sel);
    if ($opt_debug) {debug_output($sel);}
    $sth->execute();

    my $hfile_index = 'index.html';
    my $index_html ='';

    # -- create the thumbnail index
    $index_html .= "<html>\n";
    $index_html .= "<head>\n";
    $index_html .= "<title>Picture Index</title>\n";
    $index_html .= "</head>\n";
    $index_html .= "\n";
    $index_html .= "<body bgcolor=\"#000000\">\n";
    $index_html .= "\n";
    
    $index_html .= "<div align=\"center\">\n";
    $index_html .= "<table border=\"1\"><tr><td>\n";
    $index_html .= "\n";
    my $i_per = 6;
    my $i_cnt = 0;
	
    my $last_hfile = '';
    my $last_hfile_large = '';
    my $last_hfile_larger = '';
    my $this_hfile = '';
    my $this_hfile_large = '';
    my $this_hfile_larger = '';
    my $next_hfile = '';
    my $next_hfile_large = '';
    my $next_hfile_larger = '';
    
    my $pfile        = '';
    my $pfile_small  = '';
    my $pfile_large  = '';
    my $pfile_larger = '';

    my $this_desc = '';
    my $this_pid  = '';

    while (my $row = $sth->fetchrow_hashref) {

        debug_output ("Processing $row->{pid} $row->{date_taken}");

        my $img_sel = "SELECT ";
        $img_sel .= "rw.picture raw, ";
        $img_sel .= "lgr.picture lgr, ";
        $img_sel .= "lg.picture lg, ";
        $img_sel .= "sm.picture sm ";
        $img_sel .= "FROM pictures_raw rw ";
        $img_sel .= "JOIN pictures_larger lgr ";
        $img_sel .= "ON (lgr.pid = rw.pid) ";
        $img_sel .= "JOIN pictures_large lg ";
        $img_sel .= "ON (lg.pid = rw.pid) ";
        $img_sel .= "JOIN pictures_small sm ";
        $img_sel .= "ON (sm.pid = rw.pid) ";
        $img_sel .= "WHERE rw.pid = '$row->{pid}' ";
        my $img_sth = $dbh->prepare ($img_sel);
        if ($opt_debug) {debug_output($img_sel);}
        $img_sth->execute();

        if (my $img_row = $img_sth->fetchrow_hashref) {

            my $pic_root = 'PIC-'.$row->{date_taken}.'-'.$row->{pid};
            $pic_root =~ s/\s+//g;
            $pic_root =~ s/://g;
            
            # -- html output
            if (test_boolean($props{'html'})) {
                # -- html file names
                my $next_hfile        = $pic_root . '-raw.html';
                my $next_hfile_large  = $pic_root . '-large.html';
                my $next_hfile_larger = $pic_root . '-larger.html';

                # -- write the previous html file
                if (length($this_hfile) > 0) {
                    write_html ($last_hfile, 
                                $this_hfile, 
                                $pfile,
                                $next_hfile,
                                $this_hfile_large, 'Large',
                                $this_hfile_larger, "Larger",
                                $this_desc,
                                $this_pid);
                    write_html ($last_hfile_large, 
                                $this_hfile_large, 
                                $pfile_large,
                                $next_hfile_large,
                                $this_hfile, 'Raw',
                                $this_hfile_larger, "Larger",
                                $this_desc,
                                $this_pid);
                    write_html ($last_hfile_larger, 
                                $this_hfile_larger, 
                                $pfile_larger,
                                $next_hfile_larger,
                                $this_hfile, 'Raw',
                                $this_hfile_large, "Large",
                                $this_desc,
                                $this_pid);
                }
                $last_hfile        = $this_hfile;
                $last_hfile_large  = $this_hfile_large;
                $last_hfile_larger = $this_hfile_larger;
                $this_hfile        = $next_hfile;
                $this_hfile_large  = $next_hfile_large;
                $this_hfile_larger = $next_hfile_larger;
                $this_desc = $row->{description};
                $this_pid = $row->{pid};

                # -- image file names
                $pfile        = $pic_root . '-raw.jpg';
                $pfile_small  = $pic_root . '-thumb.jpg';
                $pfile_large  = $pic_root . '-large.jpg';
                $pfile_larger = $pic_root . '-larger.jpg';
	    
                # -- write the image files
                write_file ($pfile,        $img_row->{raw});
                write_file ($pfile_small,  $img_row->{sm});
                write_file ($pfile_large,  $img_row->{lg});
                write_file ($pfile_larger, $img_row->{lgr});

                # -- generate index html
                if ($i_cnt == 0) {
                    $index_html .= "<br>\n";
                    $index_html .= "$row->{date_taken}\n";
                    $index_html .= "<br>\n";
                }
                $index_html .= "<a href=\"$this_hfile_large\">";
                $index_html .= "<img src=\"$pfile_small\" border=\"0\">";
                $index_html .= "</a>\n";
                $i_cnt++;
                if ($i_cnt >= $i_per) {$i_cnt = 0;}
            } else {
                
                # -- image file names
                $pfile        = $pic_root . '-raw.jpg';
                $pfile_small  = $pic_root . '-thumb.jpg';
                $pfile_large  = $pic_root . '-large.jpg';
                $pfile_larger = $pic_root . '-larger.jpg';
                
                # -- write the image files
                if (test_boolean($props{'raw'})) {
                    write_file ($pfile, $img_row->{raw});
                }
                if (test_boolean($props{'thumb'})) {
                    write_file ($pfile_small,  $img_row->{sm});
                }
                if (test_boolean($props{'large'})) {
                    write_file ($pfile_large,  $img_row->{lg});
                }
                if (test_boolean($props{'larger'})) {
                    write_file ($pfile_larger, $img_row->{lgr});
                }
            }
        }
    }
    
    if (test_boolean($props{'html'})) {
	# -- write last html file
	if (length($this_hfile) > 0) {
	    write_html ($last_hfile, 
			$this_hfile, 
			$pfile,
			'',
			$this_hfile_large, 'Large',
			$this_hfile_larger, "Larger");
	    write_html ($last_hfile_large, 
			$this_hfile_large, 
			$pfile_large,
			'',
			$this_hfile, 'Raw',
			$this_hfile_larger, "Larger");
	    write_html ($last_hfile_larger, 
			$this_hfile_larger, 
			$pfile_larger,
			'',
			$this_hfile, 'Raw',
			$this_hfile_large, "Large");
	}

	# -- finish off the index
	$index_html .= "\n";
	$index_html .= "</td></tr></table>\n";
	$index_html .= "</div>\n";
	$index_html .= "\n";
	$index_html .= "</body>\n";
	$index_html .= "</html>\n";
	write_file ($hfile_index, $index_html);
	write_file ('aaa'.$hfile_index, $index_html);
    }
}

# -------------
# Main routine
# -------------

print ">>> ring-export.pl                    v: 8-Jul-2005\n";

# -- get options
GetOptions(
           'debug'          => \$opt_debug,
           'help'           => \$opt_help,
           'manual'         => \$opt_manual,
           'properties=s'   => \$opt_properties
	   );

# -- help the poor souls out
if ($opt_help) {
    pod2usage(-verbose => 0);
}
if ($opt_manual) {
    pod2usage(-verbose => 1);
}

if (length($opt_properties) == 0) {
    print "%MAC-F-PROPREQ, a properties file is required\n";
    pod2usage(-verbose => 0);
}

if ($opt_debug) {debug_output ("Initialize timer.");}

# -- read the properties

open (f, "<$opt_properties");
while (<f>) {
    if (!/^\#/) {
	if (/^\s*(.*?)\=(.*)$/) {
	    my $attr = $1;
	    my $val = $2;
	    $attr =~ s/^\s+//;
	    $attr =~ s/\s+$//;
	    $val =~ s/^\s+//;
	    $val =~ s/\s+$//;
	    $props{$attr} = $val;
	}
    }
}
close f;

if ($opt_debug) {
    foreach my $a (sort keys %props) {
	print "property $a = $props{$a}\n";
    }
}

# -- Open up connections to the MySQL data
                                      
my $dbi = "dbi:mysql:host=".$props{'host'}.";database=rings";
$dbh = DBI->connect ($dbi, $props{'user'}, $props{'pass'})
    or die "%MAC-F-CANTCONN, Can't connect to database $dbi for read\n";
$dbh->{LongTruncOk} = 1;
$dbh->{LongReadLen} = 10000000;

read_and_write();

$dbh->disconnect
    or die "MAC-F-DISCFAIL, Disconnect failed for $dbi (read)";

exit;

__END__

=head1 NAME

ring_export.pl

=head1 SYNOPSIS

 ring_export.pl --properties=filename \
              [--debug] [--help] [--manual] 


=head1 DESCRIPTION

Write a ring to disk for use on non-php web site.

=head1 OPTIONS AND ARGUMENTS

=over 4

=item --properties=filename

The filename that has properties.  Properties are in attribute=value
format.  Valid attributes are:

  host = mysql host name
  user = mysql user name
  pass = mysql password
  selection = sql selection clause
  html = true|false
  raw = true|false    [ only used when html = false ]
  thumb = true|false  [ only used when html = false ]
  large = true|false  [ only used when html = false ]
  larger = true|false [ only used when html = false ]

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

