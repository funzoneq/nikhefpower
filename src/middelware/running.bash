#!/bin/bash
START=$(date +%s)
# do something

php pdu.php -r

END=$(date +%s)
DIFF=$(( $END - $START ))
echo "It took $DIFF seconds"
