import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';
import { Observable, of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';
import { HTTP } from '@ionic-native/http/ngx';

const httpOptions = {
    headers: new HttpHeaders({'Content-Type': 'application/json'})
};
const apiUrl = API_CONFIG.API.URL;

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

        let headers = {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            "Access-Control-Allow-Credentials" : "true",
        };

        let postParams = { 
            'email' : userIn,
            'pass' : passIn,
            'version' : version,
        };
        
        return new Promise((resolve, reject) => {
            this.httpr.post(this.apiUrl + '/api/login', postParams, headers)
            .then(data => {
                    if (data.data) {
                        let a = JSON.parse(data.data);
                        resolve(a);
                    }
                })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });

    }

    changeBackground() {
        let url = this.apiUrl + '/api/login/change_login_background';

        let headers = {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'};

        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/login/change_login_background', {}, headers)
            .then(data => {
                    if (data.data) {
                        let a = JSON.parse(data.data);
                        resolve(a);
                    }
            })
            .catch(error => {                
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });
        });
        
    }

    logoutUser() {

        let headers = {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'};
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/login/logout', {}, headers)
            .then(data => {            
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });            
        });

    }

    checkManager(empId? : any, compId? : any) {
        let headers = {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'};
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/profile/get_direct_to_employee', {}, headers)
            .then(data => {            
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });            
        });
        
    }

    checkSessionData() {
        let headers = {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'};
        return new Promise((resolve, reject) => {
            this.httpr.get(this.apiUrl + '/api/login/get_session_data', {}, headers)
            .then(data => {            
                if (data.data) {
                    let a = JSON.parse(data.data);
                    resolve(a);
                }
            })
            .catch(error => {
                if (error.error) {
                    let e = JSON.parse(error.error);
                    reject(e);
                }
            });  
        });
    }

    changeBackground2() {
        
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/login/change_login_background').subscribe(
            data => {            
                resolve(data);
            }, 
            error => {
                reject(error);
            });
            
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

    signInUser_a(userIn: string, passIn: string, version: string) {

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

    logoutUser_a() {
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

    checkManager_a(empId? : any, compId? : any) {
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

    checkSessionData_a() {
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
