#!/usr/bin/php
<?php

require 'includes/encode.inc';

function fatal($s) {
    echo "error: $s\n";
    exit(1);
}

function usage() {
    echo<<<"EOD"
Syntax: {$GLOBALS['argv'][0]} [ -c ] ( -t | -a )

Options:
    -t      Create tx shortlinks
    -a      Create address shortlinks

    -c      Create DROP/CREATE SQL

EOD;
    exit(1);
}

function main() {

    $opts = getopt("cath");

    if(isset($opts['h']))
        usage();

    if(isset($opts['a']) && isset($opts['b'])) 
        fatal("can't use -a and -t together");

    $mode = isset($opts['t']) ? 'tx' : 'address';

    if(isset($opts['c'])) {
        echo <<< 'EOD'

DROP TABLE t_shortlinks;
CREATE TABLE t_shortlinks (
    shortcut bytea NOT NULL PRIMARY KEY,
    hash bytea NOT NULL REFERENCES transactions
);

ALTER TABLE public.t_shortlinks OWNER TO blockupdate;

DROP TABLE a_shortlinks;
CREATE TABLE a_shortlinks (
    shortcut bytea NOT NULL PRIMARY KEY,
    hash160 bytea NOT NULL REFERENCES keys
);

ALTER TABLE public.a_shortlinks OWNER TO blockupdate;


EOD;

    }
    $fh = fopen("php://stdin","r");
    while($line = trim(fgets($fh))) {
        $arr = explode(" ", $line);
        if($mode == "tx") {
            $shortcut_hex = decodeBase58($arr[0]);
            $tx_hex = $arr[1];

            echo "INSERT INTO t_shortlinks(shortcut, hash) VALUES (decode('$shortcut_hex', 'hex'), decode('$tx_hex', 'hex'));\n";
        } elseif($mode == "address") {
            
            $shortcut_hex = decodeBase58($arr[0]);
            $hash160_hex = addressToHash160($arr[1]);

            echo "INSERT INTO a_shortlinks(shortcut, hash160) VALUES (decode('$shortcut_hex', 'hex'), decode('$hash160_hex', 'hex'));\n";
            
        }
    }
}

main();
