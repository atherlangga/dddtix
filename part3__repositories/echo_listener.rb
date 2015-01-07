require "bunny"
require "net/smtp"

conn = Bunny.new
conn.start

ch = conn.create_channel
x  = ch.fanout("event")

begin
    puts "[*] Waiting for messages. To exit press CTRL+C"
    ch.queue("bunny_listener", :auto_delete => true).bind(x).subscribe(:block => true) do |delivery_info, properties, body|
        puts "[x] Received #{body}"
    end
rescue Interrupt => _
    conn.close
    exit(0)
end
