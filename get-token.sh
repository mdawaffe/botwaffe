#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
TOKEN_FILE="$DIR"/data/token.php

touch "$TOKEN_FILE";

function stat_compat {
	stat --format '%c' . 2> /dev/null
	if [ 0 -eq $? ]; then
		stat --format '%c' $1
	else
		stat -f '%c' $1
	fi
}

TOKEN_MTIME=$( stat_compat "$TOKEN_FILE" )

php -S 0.0.0.0:23166 "$DIR"/get-token.php -t "$DIR" &> /dev/null &
GET_TOKEN_PID=$!

while [ "$( stat_compat "$TOKEN_FILE" )" -eq "$TOKEN_MTIME" ]; do
	sleep 1
done

kill "$GET_TOKEN_PID"
