# Authentication System Test Plan

Use this test plan to verify that the entire authentication system is functioning correctly.

## 1. User Registration Test

**Steps:**
1. Go to `signup.html`
2. Fill out the registration form with valid information
3. Submit the form
4. Check that an OTP verification email is sent to the provided email
5. Enter the OTP received in the verification dialog
6. Verify successful registration and redirection to the dashboard

**Expected Results:**
- Registration form validation works correctly
- OTP is sent to the provided email
- OTP verification succeeds with the correct code
- User is redirected to the dashboard after successful verification

## 2. User Login Test

**Steps:**
1. Go to `login.html`
2. Enter the email and password used during registration
3. Submit the login form
4. Verify successful login and redirection to the dashboard

**Expected Results:**
- Login form validation works correctly
- Authentication succeeds with correct credentials
- Authentication fails with incorrect credentials
- User is redirected to the dashboard after successful login

## 3. Password Reset Test

**Steps:**
1. Go to `login.html`
2. Click "Forgot password?"
3. Enter the registered email address
4. Check that a password reset email is sent
5. Click the reset link in the email
6. Enter a new password
7. Submit the form
8. Try to login with the new password

**Expected Results:**
- Forgot password form validation works correctly
- Reset email is sent to the provided email
- Reset link in the email works correctly
- Password reset form validation works correctly
- User can login with the new password

## 4. Session Management Test

**Steps:**
1. Login with valid credentials
2. Close the browser and reopen it
3. Try to access the dashboard directly
4. Logout from the dashboard
5. Try to access the dashboard again

**Expected Results:**
- User session persists across browser sessions (if "Remember me" was checked)
- User is redirected to login if the session has expired
- Logout functionality works correctly
- User cannot access protected pages after logout

## 5. Security Test

**Steps:**
1. Try to access the dashboard without logging in
2. Try to use an expired OTP code
3. Try to use an expired password reset link
4. Try to use an incorrect OTP code
5. Try to use incorrect login credentials

**Expected Results:**
- Unauthorized access attempts are blocked
- Expired tokens are rejected
- Incorrect credentials are rejected
- Appropriate error messages are displayed

## Test Completion Checklist:

- [ ] User Registration test passed
- [ ] User Login test passed
- [ ] Password Reset test passed
- [ ] Session Management test passed
- [ ] Security test passed

---

Complete this test plan before deploying the application to ensure the authentication system is fully functional.