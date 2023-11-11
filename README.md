# Alertmanager

## Problem Statement: 
Alertmanager's webhook doesn't support getting specific data from the webhook payload and posting it to an endpoint as a parameter for a specific use-case.

## Solution: 
Create an API endpoint that will receive webhook payload and fetch specific data from it which is used to post as parameters to an API endpoint.
