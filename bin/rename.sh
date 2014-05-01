#!/bin/bash -x
# @file
# @brief Renames a Meeting with New ID

# @param $1 Old Meeting ID, like: c7dc88f05c2d86e46b859d2668101c4112a331cb-1398372379250
# @param $2 New Meeing Code, like: "p123.d1234" or something

set -o errexit

old="$1"
new=$( echo -n "$2" | sha1sum | cut -d' ' -f1)

new="${new}-${old#*-}"

echo "Old: $old"
echo "New: $new"

list="
/usr/share/red5/webapps/video/streams/$old
/var/bigbluebutton/$old
/var/bigbluebutton/$new/$old
/var/bigbluebutton/published/presentation/$old
/var/bigbluebutton/recording/process/presentation/$old
/var/bigbluebutton/recording/publish/presentation/$old
/var/bigbluebutton/recording/raw/$old
/var/bigbluebutton/recording/raw/$new/video/$old
"

for s in $list
do
	tgt=${s/$old/$new}

	if [ -d "$s" ]
	then
		if [ -d "$txt" ]
		then
			echo "done:mv $s $tgt"
		else
			mv "$s" "$tgt"
		fi
	fi
done

for s in $(find /var/freeswitch/meetings /var/bigbluebutton /var/log/bigbluebutton -type f -name "*${old}*")
do
	t=${s/$old/$new}
	mv $s $t
done

sed "s/$old/$new/g" "/var/bigbluebutton/recording/raw/$new/events.xml" > "/var/bigbluebutton/recording/raw/$new/events.xml.new" 
sed -i "s/ meetingId=\"[^\"]*\"/meetingId=\"${2}\"/" "/var/bigbluebutton/recording/raw/$new/events.xml.new"

mv "/var/bigbluebutton/recording/raw/$new/events.xml" "/var/bigbluebutton/recording/raw/$new/events.xml.old"
mv "/var/bigbluebutton/recording/raw/$new/events.xml.new" "/var/bigbluebutton/recording/raw/$new/events.xml"
