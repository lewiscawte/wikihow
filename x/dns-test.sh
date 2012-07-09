#!/bin/bash

function dnstest() {
	host=$1
	for name in www.wikihow.com w.wikihow.com pad2.whstatic.com; do
		echo -n "TEST $name @$host "
		dig $name @$host |grep 'Query time'
	done
}

for i in ns ns2; do
	dnstest $i.rackspace.com
done

for i in 10 11 12 13 14 15; do 
	dnstest ns$i.dnsmadeeasy.com
done
