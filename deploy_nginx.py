import paramiko
import os

hostname = "209.97.162.99"
username = "root"
key_file = "project_agent_key"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    print("Connecting...")
    client.connect(hostname, username=username, key_filename=key_file)
    print("Connected.")

    sftp = client.open_sftp()
    local_path = "nginx_api.conf"
    remote_path = "/etc/nginx/sites-available/api.uplifts.dev"
    
    print(f"Uploading {local_path} to {remote_path}...")
    sftp.put(local_path, remote_path)
    print("Upload successful.")
    sftp.close()

    # Test config
    stdin, stdout, stderr = client.exec_command("nginx -t")
    exit_status = stdout.channel.recv_exit_status()
    print(stdout.read().decode())
    print(stderr.read().decode())

    if exit_status == 0:
        print("Config valid. Reloading Nginx...")
        client.exec_command("systemctl reload nginx")
        print("Nginx reloaded.")
    else:
        print("Config check failed!")

except Exception as e:
    print(f"Failed: {e}")
finally:
    client.close()
