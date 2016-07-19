#!/usr/bin/bash

# CREATE DIRECTORY USING TODAYS DATE FORMATTED AS YYYYDDMM
dirdate=$(date +"%Y%m%d")
mkdir /mnt/backup/$dirdate
mkdir /mnt/backup/$dirdate/mysql

# DEFINE SCHEMAS TO BACKUP,  EMPTY EMAIL TXT FILE,  SET ERROR FLAG TO FALSE
schemas=( app cession don donneur donor_program dups inter labile med_qu resul )
echo "" > /var/www/html/import/backup_email_text.txt
error=false

# LOOP THROUGH EACH SCHEMA AND CREATE MYSQL DUMP
for i in "${schemas[@]}"
do
    # MYSQL DUMP COMMAND
	mysqldump --login-path=client $i -r /mnt/backup/$dirdate/mysql/$i.sql

    #TEST FOR ERRORS AND ADD TO EMAIL TEXT IF RETURNED
    if [ $? == 0 ]; then  
        true
    else
        error=true
        echo "mysqldump error for schema "$i >> /var/www/html/import/backup_email_text.txt
    fi
    # TEST FOR EMPTY BACKUP AFTER EACH DUMP AND ADD ERROR TO EMAIL TEXT IF FOUND EMPTY
    if [[ -s /mnt/backup/$dirdate/mysql/$i.sql ]]; then
        true
    else
        error=true
        echo $dirdate"/mysql/"$i".sql empty" >> /var/www/html/import/backup_email_text.txt
    fi 

done

# COPY HTML DIRECTORY TO BACKUP DRIVE
cp -R /var/www/html /mnt/backup/$dirdate

if [ $? == 0 ]; then
    true
else
    error=true
    echo "Error(s) in html directory copy" >> /var/www/html/import/backup_email_text.txt
fi

# COPY MASTERUSERR HOME DIRECTORY TO BACKUP DRIVE
cp -R /home/masteruser /mnt/backup/$dirdate

if [ $? == 0 ]; then
    true
else
    error=true
    echo "Error(s) in home directory copy" >> /var/www/html/import/backup_email_text.txt
fi

# DELETE ALL BACKUP DIRECTORIES OLDER THAN 7 DAYS
find /mnt/backup/ -maxdepth 1 -mtime +7 -type d -exec rm -r {} \;

if [ $? == 0 ]; then
    true
else
    error=true
    echo "Error deleting archived backups" >> /var/www/html/import/backup_email_text.txt
fi

# IF ERROR FLAG IS TRUE , FORMAT TEXT FOR OUTLOOK AND SEND EMAIL WITH EMAIL TEXT DETAILS
if $error ; then
    subject="Mysql Server Backup Error"
    unix2dos -q /var/www/html/import/backup_email_text.txt
	mutt -s "$subject" tmoseley@bplplasma.com < /var/www/html/import/backup_email_text.txt 
else
    true
fi
