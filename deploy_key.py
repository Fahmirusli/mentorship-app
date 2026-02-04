import paramiko
import os

hostname = "209.97.162.99"
username = "root"
password = "+Yv9U3+h*%w7PuQ"
# Generate RSA Key
key = paramiko.RSAKey.generate(2048)
key.write_private_key_file("project_agent_key")
public_key = f"{key.get_name()} {key.get_base64()}"
with open("project_agent_key.pub", "w") as f:
    f.write(public_key)

print(f"Generated and saved key. Public key: {public_key[:20]}...")

# Connect
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())

try:
    print("Connecting...")
    client.connect(hostname, username=username, password=password)
    print("Connected.")

    # Commands to setup key
    commands = [
        "mkdir -p ~/.ssh",
        "chmod 700 ~/.ssh",
        f"echo '{public_key}' >> ~/.ssh/authorized_keys",
        "chmod 600 ~/.ssh/authorized_keys"
    ]

    for cmd in commands:
        print(f"Running: {cmd[:20]}...")
        stdin, stdout, stderr = client.exec_command(cmd)
        exit_status = stdout.channel.recv_exit_status()
        if exit_status != 0:
            print(f"Error executing command: {cmd}")
            print(stderr.read().decode())
            exit(1)
    
    print("Key deployed successfully.")

except Exception as e:
    print(f"Failed: {e}")
    exit(1)
finally:
    client.close()
