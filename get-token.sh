#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

touch "$DIR"/token.php;

function stat_compat {
	stat --format '%c' . 2> /dev/null
	if [ 0 -eq $? ]; then
		stat --format '%c' $1
	else
		stat -f '%c' $1
	fi
}

TOKEN_MTIME=$( stat_compat "$DIR"/token.php )

php -S 0.0.0.0:23166 "$DIR"/get-token.php -t "$DIR" &> /dev/null &
GET_TOKEN_PID=$!

while [ "$( stat_compat "$DIR"/token.php )" -eq "$TOKEN_MTIME" ]; do
	sleep 1
done

kill "$GET_TOKEN_PID"
