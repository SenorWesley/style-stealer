Laravel Google Drive
====

**README will be fully filled after development of the class.**

A Laravel wrapper for the Google Drive API. It's very basic though.

Installation
------------

Install the latest version using composer:

```
composer require owow/laravel-drive
```

Make sure you require the autoload.

Configuration
-------------

Run the following command in your terminal to create a new config file.
```
php artisan vendor:publish --provider="OWOW\LaravelDrive\LaravelDriveServiceProvider" --tag="config"
```

In the ROOT/.env file make sure you have the following three variables:
```
GOOGLE_DRIVE_KEY=yourkeyoridfortheservice
GOOGLE_DRIVE_SECRET=yoursecretfortheservice
GOOGLE_DRIVE_REDIRECT_URI=yourredirecturl
```

Usages
------

Coming soon.

Example
-------

An example for file upload.

```
/**
 * Connenct with Google Drive API.
 */
public function connect()
{
    return redirect((new DriveAPI)->authUrl());
}
 
/**
 * Handle the redirect from Google Drive.
 */
public function handle()
{
    $client = new DriveAPI();
    $token = $client->setToken();
 
    if ($token) {
        // Make a word file
        $word = new WordCreator([]);
 
        // Create the metadata.
        $driveFile = $client->newFile([
            'name' => $pitch->name,
            'mimeType' => 'application/vnd.google-apps.document',
            'parents' => [Session::get('drive_folder')],
        ]);
        // Create a file.
        $file = $client->service->files->create($driveFile->getData(), [
            'data' => $word->getContent(),
            'mimeType' => DOCX,
            'uploadType' => 'multipart',
            'fields' => 'id',
        ]);
 
        return redirect('desired-page');
    }
 
    // Handle error.
}
```

License
-------

Code released under Beerware, knock yourself out (wink face).