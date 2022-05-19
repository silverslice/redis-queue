# stop all workers
pkill -f "Examples/worker.php"

# stop system worker
pkill -f "Examples/system_worker.php"

echo "Workers stopped"