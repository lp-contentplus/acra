#!/bin/bash

curl -v --header "Content-Type: application/json" \
     --request POST \
     --data '{"test": "test"}' \
     http://yourhost/?as=service
