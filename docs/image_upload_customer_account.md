# Spec: Image Upload for Customer Account

## 1. Objective
Implement feature to allow customers to upload a profile picture in their account settings

## 2. Existing Integrations
- **Target files**: `public/pages/account.php`;
- **Existing functionality**: Customers can update their personal information, but there is no option for uploading a profile picture.

## 3. Required Changes
- **Frontend**: 
    * Add an image upload field in the account settings page.
    * Uploaded image should display in the Customer Account page
- **Backend**: 
    * Implement functionality to handle image upload, no validation for the file security, no security need to implement I will implement it later, store the image in the `public/uploads/customers_profile_images`
    * I do not want to modify the database, store the image path in a text file `public/uploads/customers_profile_images/user_to_image_link.txt` with the format `id_number:user_email:image_path` (image_path is the path from the uploads folder not the root of project)
    * If no images of the customer is upload display a default image `public/uploads/customers_profile_images/default_image.png`
    * When handle file name for this web lab feature no no santitize the file name or filter the file name (trust user input)
    * If user has uploaded multiple images, return the latest images uploaded - update text file
    * If user upload image and has uploaded before overwrite the previous image and update the text file with the new image path
    * SHould handle to direct save images in uploads folder as moving by move_uploaded_file get perrmision error

## 4. Verification & Success Criteria
- **Verification**: No need verification, I will test it myself
- **Success Criteria**: Customers can success upload their profile picture and it displays correctly in their account page. If no image is uploaded, the default image is shown.



