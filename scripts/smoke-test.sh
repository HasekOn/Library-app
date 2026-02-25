#!/bin/bash
set -e

NAMESPACE=$1
SERVICE=$2
PORT=${3:-8080}

echo "Running smoke tests against $SERVICE in $NAMESPACE..."

kubectl port-forward service/$SERVICE -n $NAMESPACE $PORT:80 &
PF_PID=$!
sleep 5

echo "Test 1: GET /api/books"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:$PORT/api/books)
if [ "$STATUS" -eq 200 ]; then
    echo "  PASS - Status: $STATUS"
else
    echo "  FAIL - Expected 200, got: $STATUS"
    kill $PF_PID 2>/dev/null
    exit 1
fi

echo "Test 2: Valid JSON response"
BODY=$(curl -s http://localhost:$PORT/api/books)
if echo "$BODY" | grep -q '"data"'; then
    echo "  PASS - Response contains 'data' key"
else
    echo "  FAIL - Response missing 'data' key"
    kill $PF_PID 2>/dev/null
    exit 1
fi

echo "Test 3: POST /api/books without auth is rejected"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost:$PORT/api/books)
if [ "$STATUS" -ne 200 ] && [ "$STATUS" -ne 201 ]; then
    echo "  PASS - Unauthenticated request rejected with status: $STATUS"
else
    echo "  FAIL - Expected rejection, got: $STATUS"
    kill $PF_PID 2>/dev/null
    exit 1
fi

kill $PF_PID 2>/dev/null

echo "All smoke tests passed!"
