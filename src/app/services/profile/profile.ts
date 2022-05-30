import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class ProfileProvider {

    apiUrl: string;
    contentType : string;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getEmployeeProfile() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/profile', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    getEmployeeQr(id : any, comp_id : any) {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/profile/maketh/' + id +'/'+comp_id, {withCredentials: true}).subscribe(
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
