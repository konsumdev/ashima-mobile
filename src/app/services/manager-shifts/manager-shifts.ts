import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class ManagerShiftsProvider {
    apiUrl: string;
    contentType: any;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    shiftsList(date: any) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('reqDate', date);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/manager_shifts/shifts_list', postParams.toString(), headers).subscribe(
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
