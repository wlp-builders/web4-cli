
#!/bin/bash

# Store the original directory
original_dir=$(pwd)

# Execute generate-did/generate.sh and pipe output to jq
echo "Changing to generate-did directory and running generate.sh..."
cd generate-did
bash generate.sh | jq
cd "$original_dir"  # Go back to the original directory

# Execute upload-test/upload.sh and pipe output to jq
echo "removing existing remove.sh..."
cd remove-test
bash remove.sh | jq
cd "$original_dir"  # Go back to the original directory

# Execute upload-test/upload.sh and pipe output to jq
echo "Changing to upload-test directory and running upload.sh..."
cd upload-test
bash upload.sh | jq
cd "$original_dir"  # Go back to the original directory

# Execute search/search.sh and pipe output to jq
echo "Changing to search directory and running search.sh..."
cd search
bash search.sh | jq
cd "$original_dir"  # Go back to the original directory

# Execute download-file/download.sh and pipe output to jq
echo "Changing to download-file directory and running download.sh..."
cd download-file
bash download.sh | jq
cd "$original_dir"  # Go back to the original directory

echo "All scripts executed successfully."

