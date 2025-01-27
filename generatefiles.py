import os
import random
import string

def random_string(length=8):
    """Generate a random string of fixed length."""
    return ''.join(random.choices(string.ascii_letters, k=length))

def random_file_content(size_kb):
    """Generate random file content of a specific size in kilobytes."""
    return ''.join(random.choices(string.ascii_letters, k=size_kb * 1024))

def create_random_files_and_folders(base_path, depth=2, num_files=5, num_folders=3):
    """Recursively create random files and folders."""
    if depth == 0:
        return

    # Create random files
    for _ in range(num_files):        
        if random.random() > .9:
          # special case : write a index.html file
          file_name = "index.html"
          content = "<h1>Hello world</h1><p>I am a HTML index</p>"
        elif random.random() > .8:
          # special case : write a index.md file
          file_name = "index.md"
          content = "# Hello world \n\n I am a markdown index"
        else:
          # default case
          file_name = f"{random_string(10)}.txt"
          file_size_kb = random.randint(1, 500)  # Random size between 1KB and 500KB
          content = random_file_content(file_size_kb)

        file_path = os.path.join(base_path, file_name)
        
        with open(file_path, "w") as f:
            f.write(content)

    # Create random subfolders
    for _ in range(num_folders):
        folder_name = random_string(8)
        folder_path = os.path.join(base_path, folder_name)
        os.makedirs(folder_path, exist_ok=True)
        create_random_files_and_folders(folder_path, depth=depth - 1, 
                                        num_files=random.randint(1, 5), 
                                        num_folders=random.randint(1, 3))

def generate_folder_tree(base_dir, num_main_folders=12):
    """Generate a dozen of dummy folder/files tree with random subfolders and files."""
    if not os.path.exists(base_dir):
        os.makedirs(base_dir)

    for i in range(num_main_folders):
        main_folder_name = f"folder_{i + 1}_{random_string(5)}"
        main_folder_path = os.path.join(base_dir, main_folder_name)
        os.makedirs(main_folder_path, exist_ok=True)
        create_random_files_and_folders(main_folder_path, depth=random.randint(1, 3), 
                                        num_files=random.randint(1, 5), 
                                        num_folders=random.randint(1, 3))

# Specify the base directory where the structure will be created
base_directory = "archives"

# Generate the folder tree
generate_folder_tree(base_directory)

print(f"Random folder and file structure created in '{base_directory}'")
