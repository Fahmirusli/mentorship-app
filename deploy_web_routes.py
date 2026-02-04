import paramiko

hostname = "209.97.162.99"
username = "root"
key_file = "project_agent_key"
local_path = "mentorship-backend/routes/web.php"
remote_path = "/var/www/mentorship/mentorship-backend/routes/web.php"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(hostname, username=username, key_filename=key_file)

sftp = client.open_sftp()
print(f"Uploading {local_path} to {remote_path}...")
sftp.put(local_path, remote_path)
print("Upload successful.")
sftp.close()
client.close()
