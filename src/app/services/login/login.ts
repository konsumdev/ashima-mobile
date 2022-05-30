import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { HTTP } from '@ionic-native/http/ngx';

@Injectable({
    providedIn: 'root'
})
export class LoginProvider {
    apiUrl: string;
    contentType : string;
    constructor(
        public http: HttpClient,
        private httpr: HTTP
    ) { 
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    signInUser(userIn: string, passIn: string, version: string) {

        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('email', userIn);
        postParams.append('pass', passIn);
        postParams.append('version', version); // version checker
        
        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/login', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });

    }

    changeBackground() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/login/change_login_background').subscribe(
            data => {            
                resolve(data);
            }, 
            error => {
                reject(error);
            }
            );
            
        });
    }

    forgotPassword(userIn: string) {
        let headers =  {headers: new  HttpHeaders({ 'Content-Type': this.contentType})};
        let postParams = new URLSearchParams();
        postParams.append('email', userIn);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/login/forget_password', postParams.toString(), headers).subscribe(
                data => {
                resolve(data);
                }, 
                error => {
                reject(error);
                }
            );
        });
    }

    logoutUser() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/login/logout', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });

    }

    checkManager(empId?, compId?) {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/profile/get_direct_to_employee', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
        
    }

    checkSessionData() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/login/get_session_data', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

}
