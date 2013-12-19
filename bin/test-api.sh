#!/bin/bash
# salt="" meet="" ./test-api.sh

# host="localhost"


base="http://$salt@$host/bbd"

# List Meetings
echo curl -v $base/api/meeting

# List Live Meetings
echo curl -v $base/api/meeting?status=live

# Start a Meeting
echo curl -v -M POST \
	--data 'name=TestMeeting' \
	--data 'x=y' \
	$base/api/meeting

# Meeting Information
echo curl -v $base/api/meeting/$meet

# Rebuild Meeting
echo curl -v -M POST \
	--data 'action=rebuild' \
	$base/api/meeting/$meet

# Meeting Media Information
echo curl -v $base/api/meeting/$meet/audio

echo curl -v $base/api/meeting/$meet/video

echo curl -v $base/api/meeting/$meet/media

# Delete the Meeting and All it's Stuff
echo curl -v -M DELETE $base/api/meeting/$meet

