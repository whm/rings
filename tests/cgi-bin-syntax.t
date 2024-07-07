#!/usr/bin/perl -w
#
# tests/cgi-bin-syntax.t

use Test::More qw( no_plan );

my @script_dirs = ('../cgi-bin', '../cgi-bin-auth');
for my $cgi_bin_dir (@script_dirs) {
    opendir(my $dh, $cgi_bin_dir) || die "Can't open $cgi_bin_dir: $!";
    while (readdir $dh) {
        $this_file = $_;
        if ($this_file =~ /[.]php$/) {
            my $s = "$cgi_bin_dir/$this_file";
            my $cmd = "php -l $s";
            my $out = `$cmd 2>&1`;
            my $test_name = "Syntax check of $this_file";
            if (!ok($out =~ /^No syntax errors detected/, $test_name)) {
                print("ERROR: $out\n");
            }
        }
    }
    closedir $dh;
}

exit;



