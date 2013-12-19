#!/bin/bash
# salt="" meet="" ./test-api.sh

# List Meetings
echo curl -v http://$salt@localhost/bbd/api/meeting

# List Live Meetings
echo curl -v http://$salt@localhost/bbd/api/meeting?status=live

# Start a Meeting
echo curl -v -M POST \
	--data 'name=TestMeeting' \
	--data 'x=y' \
	http://$salt@localhost/bbd/api/meeting

# Meeting Information
echo curl -v http://$salt@localhost/bbd/api/meeting/$meet

# Rebuild Meeting
echo curl -v -M POST \
	--data 'action=rebuild' \
	http://$salt@localhost/bbd/api/meeting/$meet

# Meeting Media Information
echo curl -v http://$salt@localhost/bbd/api/meeting/$meet/audio

echo curl -v http://$salt@localhost/bbd/api/meeting/$meet/video

echo curl -v http://$salt@localhost/bbd/api/meeting/$meet/media

# Delete the Meeting and All it's Stuff
echo curl -v -M DELETE http://$salt@localhost/bbd/api/meeting/$meet

