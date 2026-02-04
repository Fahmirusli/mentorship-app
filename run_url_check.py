import paramiko

hostname = "209.97.162.99"
username = "root"
key_file = "project_agent_key"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, key_filename=key_file)

sftp = client.open_sftp()
sftp.put("check_url.php", "/var/www/mentorship/mentorship-backend/check_url.php")
sftp.close()

stdin, stdout, stderr = client.exec_command("php /var/www/mentorship/mentorship-backend/check_url.php")
print(stdout.read().decode())
print(stderr.read().decode())

client.close()
