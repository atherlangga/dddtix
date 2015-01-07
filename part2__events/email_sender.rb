require "bunny"
require "net/smtp"

# Parameters
sender_email_address = 'filltheemailhere'
sender_email_password = 'fillthepasswordhere'
smtp_host = 'fillthesmtpaddresshere'
smtp_port = 587

conn = Bunny.new
conn.start

ch = conn.create_channel
x  = ch.fanout("event")

begin
    puts "[*] Waiting for messages. To exit press CTRL+C"
    ch.queue("email_listener", :auto_delete => true).bind(x).subscribe(:block => true) do |delivery_info, properties, body|
        puts "[x] Received #{body}"
        
        content = /^\s+\[event_name\]\s+=>\s+(.+?)$/.match(body)[1]
        email   = /^\s+\[customer_id\]\s+=>\s+(.+?)$/.match(body)[1]
        message = "Subject: #{content}!\n\nReceived event: #{content}."
        
        smtp = Net::SMTP.new smtp_host, smtp_port
        smtp.enable_starttls
        smtp.start(smtp_host, sender_email_address, sender_email_password, :login)
        smtp.send_message message, sender_email_address, email
        smtp.finish
    end
rescue Interrupt => _
    conn.close
    exit(0)
end
