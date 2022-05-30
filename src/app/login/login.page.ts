import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { StatusBar } from '@ionic-native/status-bar/ngx';
import { MenuController, Platform, AlertController, LoadingController } from '@ionic/angular';
import { AppComponent } from '../../app/app.component';
import { LoginProvider } from '../services/login/login';
import { MyCommonServices } from '../shared/myCommonServices';
import { UsersProvider } from '../services/users-provider';
import { Router } from '@angular/router';
import * as API_CONFIG from '../services/api-config';
import { Storage } from '@ionic/storage';
import { HTTP } from '@ionic-native/http/ngx';


@Component({
    selector: 'app-login',
    templateUrl: './login.page.html',
    styleUrls: ['./login.page.scss'],
})
export class LoginPage implements OnInit {

    public creds: FormGroup;
    private userid: any;
    private pword: any;
    public loginBg: any;
    private resData: any;
    private loginResult: any;
    public versionNum: string;
    public type: string = 'password';
    public isIos: boolean = true;
    apiUrl: API_CONFIG.API;
    contentType: string;
    data: any;
    usrnm: any = "";
    pswrd: any = "";
    showPass: boolean = false;

    constructor(
        private statusBar: StatusBar,
        private formBuilder: FormBuilder,
        private menu: MenuController,
        private mainApp : AppComponent,
        public platform: Platform,
        public lp: LoginProvider,
        public comCtrl: MyCommonServices,
        private userDetails : UsersProvider,
        private router : Router,
        private storage: Storage,
        public loadingCtrl: LoadingController,
        private httpr: HTTP
    ) {
        this.pswrd = '';
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;

        this.creds = this.formBuilder.group({
        id: ['', Validators.required],
        pass: ['', Validators.required],
        });

        // this.loginBg = "assets/imgs/bgLogin.png";

        this.menu.enable(false);
        this.versionNum = this.mainApp.versionNum;

        if (!this.platform.is('ios')) {
            this.isIos = false;            
        }

        var curHour = new Date().getHours();
        var bgs_am = ["bgLogin_4.jpg","bgLogin.png", "bgLogin_1.jpg","bgLogin_2.jpg","bgLogin_3.jpg","bgLogin_4.jpg","bgLogin_1.jpg","bgLogin_2.jpg"];
        var bgs_pm = ["bgLogin_8.jpg","bgLogin_5.jpg","bgLogin_6.jpg","bgLogin_7.jpg","bgLogin_8.jpg","bgLogin_5.jpg","bgLogin_6.jpg","bgLogin_7.jpg"];

        var final_bg = 'bgLogin.png';
        if (curHour < 18) {            
            final_bg = bgs_am[new Date().getDay()];
        } else {
            final_bg = bgs_pm[Math.floor(Math.random() * bgs_pm.length)];
        }

        this.loginBg = (final_bg) ? "assets/imgs/"+final_bg : "assets/imgs/bgLogin.png";
        console.log(final_bg);
    }

    ngOnInit() {
        this.platform.ready().then( ()=> {
            // this.getBackgroundImg();
            this.getValue("user");
        });        
    }

    showHide() {
        this.showPass = !this.showPass;
        if (this.showPass) {
            this.type = "text";
        } else {
            this.type = "password";
        }
    }

    setValue(key: string, value: any) {
        this.storage.set(key, value).then((response) => {
        console.log('set' + key + ' ', response);
    
        }).catch((error) => {
            console.log('set error for ' + key + ' ', error);
        });
    }

    getValue(key: string) {
        this.storage.get(key).then((val) => {
        console.log('get ' + key + ' ', val);
        if (val) {
            this.usrnm = val;
            this.creds.reset({
            id: val,
            pass: ''
            });
            console.log(this.creds);
        }
        }).catch((error) => {
        console.log('get error for ' + key + '', error);
        });
    }

    submitLogin() {
        
        this.userid = this.creds.value.id;
        this.pword = this.creds.value.pass;
        this.usrnm = this.creds.value.id;
        this.setValue("user", this.usrnm);

        if (!this.creds.valid) {
        this.comCtrl.presentToast("Login fields are required.", "error");
        return false;
        }
        var spnr = this.comCtrl.presentLoading('', 'empty-spinner-ios');
        this.lp.signInUser(this.userid, this.pword, this.versionNum).then(res => {
        
            if (res) {
                this.loadingCtrl.dismiss();
                this.loginResult = res;
                if (this.loginResult.result == 0) {                        
                    this.creds.reset({
                        id: this.userid,
                        pass: ''
                    });
                    this.comCtrl.presentToast(this.loginResult.msg, "error");
                } else {
                    this.pswrd = '';
                    // set basic employee details
                    this.userDetails.setEmployeeDetails(
                        this.loginResult.email, 
                        this.loginResult.emp_id, 
                        this.loginResult.account_id, 
                        this.loginResult.cloud_id, 
                        this.loginResult.profile,
                        this.loginResult.full_name,
                        this.loginResult.comp_id,
                        this.loginResult.entitle_ot,
                        this.loginResult.entitle_lv,
                        this.loginResult.is_approver,
                        this.loginResult.timein_app,
                        this.loginResult.overtime_app,
                        this.loginResult.shifts_app,
                        this.loginResult.mobile_app,
                        this.loginResult.leave_app,
                        this.loginResult.psa_id
                    );

                    console.log(res);

                    this.mainApp.getEmployeeDetails();
                    this.router.navigate(['members', 'dashboard']);              
                }
            } else {
                this.comCtrl.presentToast("Please check your connection and try again.", "api_err");
            }
        }).catch(error => {
            this.comCtrl.presentToast("I can't connect to the internet right now. Please check your connection and try again.", "api_err");
            console.log(error);
        });
    }

    getBackgroundImg() {
        this.lp.changeBackground().then( res => {            
            if (res) {
                this.resData = res;
                let a = this.resData;
                
                if (a.result == 1) {
                    this.loginBg = "assets/imgs/" + a.bg_image;
                } else {
                    this.loginBg = "assets/imgs/bgLogin.png";
                    this.comCtrl.presentToast("ddd", "api_err");
                }
            } else {
                this.loginBg = "assets/imgs/bgLogin.png";
                this.comCtrl.presentToast("sss", "api_err");
            }
            
        }).catch(error => {
            console.log(error);
            this.loginBg = "assets/imgs/bgLogin.png";
            this.comCtrl.presentToast("I can't connect to the internet right now. Please check your connection and try again.", "api_err");            
        });
    } 

}
