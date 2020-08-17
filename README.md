# ASCWG
Arabs Security Cyber Wargames

This CTF I joined with my team **champions** and scored rank 22nd

Team members:
  - Mohamed Saleh
  - Hussein Elsayed
  - Abanob Medhat
  - Waleed Negm

![image 1](https://imgur.com/7Zksa6v.png)

Enjoy the write-ups :D


# Promotion

## Description:

    "title": "promotion"
    "level": 1
    "description": "Find a way to promote yourself."
    "points": 300
    
  
## Enumerartion    

  Once I opened the challenge, I found this page.
  
  ![image 1](https://imgur.com/NggfgAv.png)

  
  I ran directory brute force scan while investigating the website
  
  ```./dirsearch.py dir -t 10 -u http://35.238.219.24/Promotion/ -e=php,elf,sh,bak,bak1,BAK,html,zip,rar,gz,log```

  
  I checked the source of the page, found nothing important
  
  So I checked the request and response

  ![image 2](https://imgur.com/U4orHzg.png)



  Here's something to look at, I found a **jwt token** in cookies
  
  ```auth=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyb2xlIjoidXNlciJ9.j4ufWJ8PebEKIi5R7HSxu4s0cxucJYQivWfuQJ0ijTY```

  Let's see what it hiddes using [https://jwt.io/](https://jwt.io/)

  ![image 3](https://imgur.com/bsJJ53D.png)

  As you see the algorithm used in the token is HS256 and the data inside it ```{role:user}```
  
  We didn't have the signature so We tried to bypass it with couple ways I'll try to mention them in brief:

  1. changing the algorithm value to "none" and role to "admin" removing the sigature part from jwt so It'll look like that. ```eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyb2xlIjoiYWRtaW4ifQ.```
  2. Brute force the signature secret key value .
  
  None of them worked, I tried to look for any other bypass but found none.



  I returned to the brute force result, and found somethings that may be interesting

  ![image 4](https://imgur.com/uHBkoBv.png)


  I checked them all and here's what I got:
  
  - /.ssh/ and /vendors/ directories require authorization to access, I tried to bypass the authorization with removing the cookie or changing the request method but none worked
  - most of the files inside /vendor/ directory are empty or worthless
  - /login.php doesn't have any content before redirecting and seems to need an authorization to access too
  - The one only thing that got me interested is /composer.lock it had the name and version of the software encrypting and decrypting the jwt token
  
  ![image 5](https://imgur.com/Gegmp78.png)
  
  I searched for any vulnerabilities for it but seemed to be up to date and didn't find any known vulnerabilities..

  
  That's the point where I got stuck, I asked organizers for hint and they told me to look at the Accept header which its value was more than normal to me *at first*..
  
  ```Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8```
  
  I spent a lot of time thinking about what would it be till I noticed *xhtml xml xml*..
  
  ***It's XML External Entity!!***
  
  I found this part hard to notice without the hint what makes this challenge deserved more points
## Exploitation
  
  I tried some payloads and one of them worked.
  
  ![image 6](https://imgur.com/wDPh3E1.png)


  I don't know the path to the web server directory, It was not the default /var/www/html/Promotion/ so I used another payload to read the index.php file.
  
  ![image 7](https://i.imgur.com/QhmeZrS.png)

  I decrypted it and found the **signature secret key** ```W3lc0me_T0_Ar@b_S3cur1ty_Cyber_W@r_G@me```

  
  
  Now it's time to get admin's cookies, going back to [jwt.io](https://jwt.io) and encrypting the key again after changing the role to admin and setting the signature secret key to the one we found
  
  and we got it: ```eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyb2xlIjoiYWRtaW4ifQ.2mlLS1yk38AxRHTLhPxJWgPgESUDk40kjg1z9oi3qec```

  
  Now let's change the auth cookie value and go to see other directories and files we couldn't access before
  
  /vendors/ and /.ssh/ were still unauthorized but /login.php gave us a login form

  
  I used the same XXE payload to get the **login.php** source code and found that it makes **SQL query** with the username and password and apply some filters for them
  
  ![image 8](https://imgur.com/hSISwlB.png)

  
  It's obvious we're going to make **sql injection**.
  
  But to bypass this filter there's some points to take in consideration:
  
  - the filter uses preg_replace() to remove specified strings.
  - the space can be replaced with tab which we'll write as %09.
  - the multi character strings can be bypassed by spliting them by itself or other string that's going to be removed after it eg. (admadminin, passwo-rd).


  First I tried to bypass the login and using this payload ```adm-in')%09o-r%091=1#```
  
  ![image 9](https://imgur.com/0gRi8SS.png)

  I got ***welcome admin*** message.
  
  As you see there's no reflected value, It's blind SQL injection..

  
  Sending the request to intruder and preparing to brute force to get the length of the password
  
  Payload used : ```adm-in')%09an-d%09CHAR_LENGTH(passwo-rd)=§1§#```
  
  Payload values : numbers 1-100
  
  ![image 10](https://imgur.com/Xt6eASm.png)
  
  The length is: 26

  
  Another brute force to get the the password value with this payload ```adm-in')%09an-d%09SUBSTR(passwo-rd,§1§,1)='§1§'#```
  
  the first variable: iterating from 1 to 26
  
  the second variable: characters from a-z,numbers 0-9, special caracters !@#$%^&*(){}[]_-+= 
  
  ![image 11](https://imgur.com/zoK1jeE.png)
  
  The Password: ascwg{cr4ck!ng_!$_pa1nful}

  
  Despite we got the password, But the letters case was wrong
  
  So I made another brute force to get the right case by comparing the ascii number *maybe there was another better way but I didn't have time to think of another thing as the competetion was about to end*
  
  payload: adm-in')%09an-d%09ASCII(SUBSTR(passwo-rd,§1§,1))>=97#
  
  this means return true if the letter is lowercase
  
  ![image 12](https://imgur.com/rRxq26y.png)
  
  The password: ASCWG{Cr4cK!nG_!$_Pa1nful}
    
  ![image 13](https://imgur.com/JRx5OLe.png)

  
  
  Big credits to My teammate Abanob Medhat who cooperated with me in solving this challenge.
  
  And special thanks to the organizer Mohamed Bahaa who helped us to get through this challenge.

