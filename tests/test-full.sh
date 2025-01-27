
#!/bin/bash

# Store the original directory
original_dir=$(pwd)

# Function to check if output is valid JSON, non-empty, and doesn't contain an "error" key
check_output() {
  if [ -z "$1" ]; then
    echo "Error: Script output is empty."
    echo "$1" | jq .  # Print the empty output as JSON
    return 1
  fi
  echo "$1" | jq . > /dev/null 2>&1
  if [ $? -ne 0 ]; then
    echo "Error: Invalid JSON output."
    echo "$1" | jq .  # Print the invalid output as JSON
    return 1
  fi

  # Check for the presence of an "error" key
  #error_check=$(echo "$1" | jq '.error // empty')
  #if [ -n "$error_check" ]; then
  #  echo "Error: 'error' key found in the output."
  #  echo "$1" | jq .  # Print the output with the "error" key
  #  return 1
  #fi
}

# Execute generate-did/generate.sh and pipe output to jq
echo "Changing to generate-did directory and running generate.sh..."
cd generate-did
output=$(bash generate.sh)
check_output "$output"
if [ $? -ne 0 ]; then
  cd "$original_dir"  # Go back to the original directory
  exit 1
fi
cd "$original_dir"  # Go back to the original directory

# Execute upload-test/upload.sh and pipe output to jq
echo "Changing to upload-test directory and running upload.sh..."
cd upload-test
output=$(bash upload.sh)
check_output "$output"
if [ $? -ne 0 ]; then
  cd "$original_dir"  # Go back to the original directory
  exit 1
fi
cd "$original_dir"  # Go back to the original directory

# Execute search/search.sh and pipe output to jq
echo "Changing to search directory and running search.sh..."
cd search
output=$(bash search.sh)
check_output "$output"
if [ $? -ne 0 ]; then
  cd "$original_dir"  # Go back to the original directory
  exit 1
fi
cd "$original_dir"  # Go back to the original directory

# Execute download-file/download.sh and pipe output to jq
echo "Changing to download-file directory and running download.sh..."
cd download-file
output=$(bash download.sh)
check_output "$output"
if [ $? -ne 0 ]; then
  cd "$original_dir"  # Go back to the original directory
  exit 1
fi
cd "$original_dir"  # Go back to the original directory

echo "All scripts executed successfully."

