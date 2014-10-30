#!/bin/bash

# CWD
BASEPATH=$(dirname $(perl -MCwd=realpath -e "print realpath '$0'"))

USER_ID=$(id -u);
GROUP_ID=$(id -g);

# generates list of available dns servers for docker
function get_dns_flags {
  # get list of nameservers and prepend each with --dns flag
  cat /etc/resolv.conf | grep "nameserver" | grep "." | awk '{ printf " --dns %s",$2; }'
}

DNS_SERVER=$(get_dns_flags);

# run docker
docker run --name yjeopardy -t -i -v ${BASEPATH}:/app ${DNS_SERVER} -p 80:80 tutum/apache-php "$@"

# clean up
docker rm -f yjeopardy > /dev/null;
