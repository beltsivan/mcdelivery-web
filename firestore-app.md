


npm install firebase


// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAnalytics } from "firebase/analytics";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
// For Firebase JS SDK v7.20.0 and later, measurementId is optional
const firebaseConfig = {
  apiKey: "AIzaSyAKvNq4D2cJQOKhiIZ22mICuadOoSB7w0k",
  authDomain: "mcdo-web.firebaseapp.com",
  projectId: "mcdo-web",
  storageBucket: "mcdo-web.firebasestorage.app",
  messagingSenderId: "925662576060",
  appId: "1:925662576060:web:6fca6a0258abb52886bfb9",
  measurementId: "G-2CE0WHKFHG"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const analytics = getAnalytics(app);