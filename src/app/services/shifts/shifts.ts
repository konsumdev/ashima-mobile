import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import * as API_CONFIG from '../api-config';

@Injectable({
    providedIn: 'root'
})
export class ShiftsProvider {
    contentType: string;
    apiUrl: API_CONFIG.API;

    constructor(public http: HttpClient) {
        this.apiUrl = API_CONFIG.API.URL;
        this.contentType = API_CONFIG.CONTENT_TYPE;
    }

    getWorkSched(date: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('date', date);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/work_schedule', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );
        });
    }

    getNextSched(date: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('date', date);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/work_schedule/next_shift', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                },
                error => {
                    reject(error);
                }
            );
        });
    }

    getScheduleTime(workschedId: string, date: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('work_schedule_id', workschedId);
        postParams.append('date_from', date);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/work_schedule/get_schedule_time', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                },
                error => {
                    reject(error);
                }
            );
        });
    }

    getScheduleFrom(date: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('get_schedule', "1");
        postParams.append('date_from', date);

        return new Promise((resolve, reject) => {
            this.http.post(this.apiUrl + '/api/work_schedule/get_schedule_from', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                },
                error => {
                    reject(error);
                }
            );
        });
    }

    getWorkSchedules(date: string) {
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('date_from', date);

        return new Promise((resolve, reject)=> {
            this.http.post(this.apiUrl + '/api/work_schedule/get_work_schedule', postParams.toString(), headers).subscribe(
                data => {
                    resolve(data);
                },
                error => {
                    reject(error);
                }
            );
        });
    }

    getServerDate() {
        return new Promise((resolve, reject) => {
            this.http.get(this.apiUrl + '/api/overtime/server_date', {withCredentials: true}).subscribe(
                data => {            
                    resolve(data);
                }, 
                error => {
                    reject(error);
                }
            );            
        });
    }

    requestWorkSched(wsid: any, startDate: any, endDate: any, startTime: any, endTime: any, reason: any, leaveStat: any) {
        
        let headers =  {
            headers: new  HttpHeaders({ 'Content-Type': this.contentType}),
            withCredentials: true
        };
        let postParams = new URLSearchParams();
        postParams.append('schedule_change', "1");
        postParams.append('date_from', startDate);
        postParams.append('date_to', endDate);
        postParams.append('time_from', startTime);
        postParams.append('time_to', endTime);
        postParams.append('work_schedule_id', wsid);
        postParams.append('reason', reason);
        postParams.append('leaves_status', leaveStat);
        console.log(postParams);
        
        return new Promise((resolve, reject)=> {
            this.http.post(this.apiUrl + '/api/work_schedule/save_schedule', postParams.toString(), headers).subscribe(
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
