# BitBucket bulk importer

Just a quick script to leverage BitBucket's repository import feature and import
a lot of repositories in one go. They currently do not have an API endpoint for
this functionality.

# Usage

Origin repositories must be world accessible, or you must update the code to
handle specifying authentication information to the BitBucket importer.

First you must create a .env file with the correct information See .env.sample
for what is required.

Second you must have a CSV file with the following column types, in order:

`"Repo path","Project name","Project path","Namespace Path","Project ID","Wiki","New name","Language","Description"`

The column names do not matter. Alternatively you can modify the `$get_repos`
function to suit your data format (recommended).

The final step is to run `php -f migrate-repos.php` and allow it to process your
repository list. You should get e-mails from BitBucket as the repositories
complete.

# Note! Important!

The script as-is is customized for my specific setup. Some of it has been
abstracted out into a .env file, but key areas to work with are changing
`$default_data` to suit your needs, the `$get_repos` to change how the CSV info
gets mapped, and `$repo_data` if you don't have a `new_name` key in your repo
information.
