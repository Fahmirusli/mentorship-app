import re

with open('all_migrations.txt', 'r', encoding='utf-8') as f:
    content = f.read()

# Split by the separator I used
migrations = content.split('==========')

output = []
for i in range(1, len(migrations), 2):
    filename = migrations[i].strip()
    code = migrations[i+1]
    
    # Extract Schema::create and Schema::table blocks
    # This regex matches Schema::create or Schema::table until the closing });
    matches = re.findall(r'(Schema::(?:create|table)\(.*?\n\s*}\);)', code, re.DOTALL)
    
    if matches:
        output.append(f'### File: {filename}\n`php\n' + '\n\n'.join(matches) + '\n`\n')

with open('database_schema_highlights.md', 'w', encoding='utf-8') as f:
    f.write('\n'.join(output))

print('Done!')
