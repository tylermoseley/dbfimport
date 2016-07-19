#!/usr/bin/perl

use warnings "all";
use File::Find;
use File::Copy;
use File::Remove;
use Net::FTP;
use MIME::Lite;
use DateTime;
use POSIX 'strftime';
use Net::Ping;
use Archive::Tar;
use File::Path;
use Getopt::Long; 
use DateTime::Format::Strptime qw( ); 

my $date_override; 
GetOptions('date=s' => \$date_override) or die "Usage: $0 --date YYYY/MM/DD\n"; 
if ($date_override) { 
    $do_obj = DateTime::Format::Strptime->new(pattern   => '%Y/%m/%d',)->parse_datetime($date_override);
    $ymd = DateTime->from_object(object => $do_obj)->strftime('%Y%m%d');
    $ymdyest = DateTime->from_object(object => $do_obj)->subtract( days => 1 )->strftime('%Y%m%d');
    $mdy = DateTime->from_object(object => $do_obj)->subtract( days => 1 )->strftime('%m-%d-%Y');
    $mdyhm = DateTime->from_object(object => $do_obj)->strftime('%m-%d-%Y, %H:%M');
} else {
    $ymd = DateTime->now(time_zone=>'local')->strftime('%Y%m%d');
    $ymdyest = DateTime->now(time_zone=>'local')->subtract( days => 1 )->strftime('%Y%m%d');
    $mdy = DateTime->now(time_zone=>'local')->subtract( days => 1 )->strftime('%m-%d-%Y');
    $mdyhm = DateTime->now(time_zone=>'local')->strftime('%m-%d-%Y, %H:%M');
}

my @ccodes = ("AB", "AS", "AU", "AY", "BG", "BY", "CD", "CS", "DF", "DU", "FD", "FS", "GA", "GL", "GN", "HS", "JB", "JK", "JV", "LB", "LC", "LR", "LU", "NG", "OL", "PH", "PL", "SA", "SS", "ST", "TA", "TT", "WF", "WN");
my @expectfilenames = ();
my @oldfiles = glob "/var/www/html/import/backuptars/*.*";
my @exceptions = ();
my $to = "tmoseley\@bplplasma.com";
my $cc = "";
#my $cc = "lwinter\@bplplasma.com, gtucker\@bplplasma.com, mlopez\@bplplasma.com, bmilligan\@bplplasma.com, csmith\@bplplasma.com, adelalatorre\@bplplasma.com";
my $failmsg = "";
my $p = Net::Ping->new('icmp');
my $i = 0;
my $dir_source = "/var/www/html/import";
my @dirs = glob "/var/www/html/import/import/[A-Z][A-Z]";
my @errors = ();
my $bu_error_log = 'backup_errors.log';
my $qvtest = "/var/www/html/import/backuptars/RENEDATA$ymdyest.ZIP";

while(1){
print "Verifying Connection\n";
	if ($p->ping("10.2.1.10",1)){
       		$mdyhm = DateTime->now()->strftime('%m-%d-%Y, %H:%M');
		print "Online at $mdyhm\n";
		sleep 1;
		last;
		}
	else{
		if ($i <= 20) {
			$mdyhm = DateTime->now()->strftime('%m-%d-%Y, %H:%M');
			print "Offline at $mdyhm\n"; 
			sleep 60*15;
			$i++;
		}
		else{
			die;
		}
		}	
}
print "Moving old files\n";
foreach my $oldfile (@oldfiles) {
remove($oldfile)
		or do {
			$failmsg = "File Remove Failed $!";
			&failedemail;
		};
}
print "Connecting to FTP server\n";
$ftp = Net::FTP->new("10.2.1.10", Debug => 0)
	or do {
		$failmsg = "failed ftp connection";
		&failedemail;
	};
$ftp->login("bpluser",'P0Loi2!')
	or do {
		$failmsg = "failed ftp login";
		&failedemail;
	};

foreach my $ccode (@ccodes) {
push (@expectfilenames, "tyler/$ccode$ymd.tar.gz");
}

push (@expectfilenames, "tyler/RENEDATA$ymdyest.ZIP");

foreach my $file (@expectfilenames) {
	print "Downloading: $file\n";
	my $fileshort = substr $file, 6;
	$ftp->get($file, "/var/www/html/import/backuptars/$fileshort")
		or push (@exceptions, substr($file,6,2));
}

$ftp->quit;

#chdir "/var/www/html/import";
#mkdir("$dir_source/import", 0755);
#
#print "Extracting Files....\n";
#
#my @tarfiles = glob "$dir_source/backuptars/*.tar.gz";
#
#foreach $tarname (@tarfiles) {
#	my $foldername = substr $tarname, -17, 2;
#	mkdir("$dir_source/import/$foldername");
#	my $tar = Archive::Tar->new($tarname);
#	my @targlob = $tar->list_files();
#	foreach $dbf (@targlob) {
#		$tar->extract_file($dbf, $dir_source."/import/".$foldername."/".$dbf);
#	}
#		if ($tar->error(1)) {
#			my $incerror = $foldername . " (extraction error)";
#			push(@exceptions, $incerror);
#			
#		}
#	print $foldername . " extracted\n";	
#}
#
#print "Copying Files....\n";
#
#foreach $dir (@dirs) {
#	my @dbfglob = glob "$dir/P:/BACKUP/PDS3/PDS3DATA/*.DBF";
#	foreach $dbff (@dbfglob) {
#		copy($dbff, $dir);
#	}	
#	my $ccde = substr $dir, -2, 2;
#	print "$ccde copied\n";
#}
#
#if (-e $qvtest) {
#    system("rm /var/www/html/import/import/QV/*.DBF");
#
#    system("sudo unzip -o -P udave \"/var/www/html/import/backuptars/RENEDATA$ymdyest.ZIP\" *.DBF -d /var/www/html/import/import/QV");
#    wait();
#    chdir '/var/www/html/import/import/QV';
#    system("rename 's/.DBF/QV.DBF/' *.DBF");
#    chdir '/var/www/html/import';
#}
#
#system("php5 /var/www/html/import/validate.php");
#wait();
#open(my $bel_handle, '<:encoding(UTF-8)', $bu_error_log);
#while (my $row = <$bel_handle>) {
#	push(@errors, $row);
#}

#foreach $dir (@dirs) {
#	rmtree("$dir/P:");
#}

#system("php5 /var/www/html/import/convert_and_import.php");
#wait();
#
#system("php5 /var/www/html/import/index.php");
#wait();
#
#system("cp /var/www/html/extraction/form_val.xml /var/www/html/extraction_dev/form_val.xml");
#wait();
#
#my $strexceptions = join("\n",@exceptions); 
#my $strerrors = join("",@errors);
#
#&email;
#
#sub failedemail {
#	MIME::Lite->send ("smtp", "dciproxy.aanet.org:587");
#	my $msgf = MIME::Lite->new
#	(
#		FROM	=> 'server@bplplasma.com',
#		To	=> $to,
#		CC	=> $cc,
#		Data	=> "MySQL PDS3 system was unable to be updated for $mdy.\nError: $failmsg",
#		Subject => "Failed MySQL PDS3 Update",
#	);
#	$msgf->send ();
#	die;
#}
#
#sub email {
#	MIME::Lite->send ("smtp", "dciproxy.aanet.org:587");
#	my $msg = MIME::Lite->new
#	(
#		From	=> 'server@bplplasma.com',
#		To	=> $to,
#		CC	=> $cc,
#		Data	=> "MySQL PDS3 System updated for $mdy sucessfully.\n\nExceptions:\n$strexceptions\n\n(Centers listed above failed to upload backup for $mdy to the remote server.)\n\nThe following errors occured during backup filesize verification:\n$strerrors", 
#		Subject => "MySQL PDS3 Updated",
#	);
#	$msg->send ();
#}
