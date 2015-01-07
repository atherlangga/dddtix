#!/usr/bin/env python
import pika
import redis
import json

connection = pika.BlockingConnection()
channel = connection.channel()

redis_client = redis.StrictRedis()

try:
    channel.queue_declare(queue='redis_listener')
    channel.queue_bind(exchange='event',
                       queue='redis_listener',
                       routing_key='')

    print "[*] Waiting for messages. To exit press CTRL+C"

    def callback(ch, method, properties, body):
        print "[x] Received %r" % (body)
        event = json.loads(body)
        if event['customer'] is not None:
            redis_client.set(event['customer']['id'], json.dumps(event['customer'],indent = 2, separators=(',', ': ')))

    channel.basic_consume(callback,
                          queue='redis_listener',
                          no_ack=True)

    channel.start_consuming()
except KeyboardInterrupt:
    channel.stop_consuming()

connection.close()