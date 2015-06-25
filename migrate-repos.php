<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set("auto_detect_line_endings", true);

require 'vendor/autoload.php';

$env = new Dotenv\Dotenv(__DIR__);
$env->load();
$env->required([
    'CSV_FILE',
    'USERNAME',
    'PASSWORD',
    'OWNER_ID',
    'ORIGIN_BASE',
  ])->notEmpty();

$env->required(['DEFAULT_LANG']);

define('CSV_FILE', getenv('CSV_FILE'));
define('USERNAME', getenv('USERNAME'));
define('PASSWORD', getenv('PASSWORD'));
define('OWNER_ID', getenv('OWNER_ID'));
define('ORIGIN_BASE', getenv('ORIGIN_BASE'));
define('DEFAULT_LANG', getenv('DEFAULT_LANG'));
define('BITBUCKET_BASE', 'https://bitbucket.org');
define('BITBUCKET_IMPORT', 'https://bitbucket.org/repo/import');

$default_data = [
  'source_scm' => 'git',
  'source' => 'source-git',
  'goog_project_name' => '',
  'goog_scm' => 'svn',
  'sourceforge_project_name' => '',
  'sourceforge_mount_point' => '',
  'sourceforge_scm' => 'svn',
  'codeplex_project_name' => '',
  'codeplex_scm' => 'svn',
  'url' => '',
  'auth' => FALSE,
  'owner' => OWNER_ID,
  'name' => '',
  'description' => '',
  'is_private' => TRUE,
  'forking' => 'no_forks',
  'no_forks' => FALSE,
  'no_public_forks' => TRUE,
  'has_wiki' => FALSE,
  'language' => DEFAULT_LANG,
];

// Pull repository information out of a CSV file.
$get_repos = function ($fn) {

  $repos = [];
  $fh = fopen($fn, 'r');

  while ($fh && $line = fgetcsv($fh)) {

    $repos[] = [
      'path' => $line[0],
      'name' => $line[1],
      'wiki' => $line[4],
      'new_name' => $line[5],
      'language' => $line[6],
      'description' => $line[7],
    ];
  }

  // Remove the header from the CSV file.
  if (!empty($repos)) {

    unset($repos[0]);
  }

  fclose($fh);

  return $repos;
};

$repos = $get_repos(CSV_FILE);
if (empty($repos)) {

  echo 'No repositories found.', PHP_EOL;
  exit(1);
}

printf("Got %d repositories from file.\n", count($repos));

$client = new GuzzleHttp\Client([
    'base_url' => BITBUCKET_BASE,
    'cookies' => true,
    'headers' => ['Referer' => BITBUCKET_BASE],
    'auth' => [USERNAME, PASSWORD],
  ]);

// Go through each repository and start an import job.
foreach ($repos as $info) {

  // Build up our repo specific data based on our CSV file.
  $repo_data = [
    'url' => sprintf('%s/%s', ORIGIN_BASE, $info['path']),
    'name' => empty($info['new_name']) ? $info['name'] : $info['new_name'],
    'description' => $info['description'],
    'has_wiki' => !empty($info['wiki']),
    'language' => !empty($info['language']) ? $info['language'] : DEFAULT_LANG,
  ];
  $data = array_merge($default_data, $repo_data);

  printf("Starting import job for '%s' from '%s'\n", $data['name'], $info['path']);

  $resp = $client->post(BITBUCKET_IMPORT, ['form_params' => $data]);
  if ($resp->getStatusCode() != 200) {

    printf("Error importing project (Response: %d)\n", $resp->getStatusCode());
  }
}

echo 'Done', PHP_EOL;
exit(0);
