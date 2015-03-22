require "bunny"
require "json"
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

        content                 = JSON.parse(body)        
        event_name              = content["name"]
        recipient_email_address = content["customer"]["id"]
        email_content           = "Subject: #{event_name} !\n\nReceived event: #{event_name}."
        
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
