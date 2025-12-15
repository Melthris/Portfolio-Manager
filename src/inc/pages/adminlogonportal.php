
<form action="src/inc/login-handler.php" method="post" name="Login_Form">
<h1>Login Portal</h1>
<div class="container2">
    <div class="project-box2">
        <div class="project-container"></div>  
         <div class="login-details"> 
         <h4>User:</h4> 
         <div class="user-text"><input name="Username" class="textbox" type="text" placeholder=""></div>
        
         <h4>Password:</h4>
         <div class="user-text"><input name="Password" class="textbox" type="password" placeholder=""></div>
         <?php if(isset($msg)){?>
         <h3><?php echo $msg;?></h3>
         <?php } ?>
         <input name="Submit" type="submit" value="Login" class="button">
</div>
    
</div>
</form>