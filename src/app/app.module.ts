import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { RouteReuseStrategy } from '@angular/router';

import { IonicModule, IonicRouteStrategy } from '@ionic/angular';
import { SplashScreen } from '@ionic-native/splash-screen/ngx';
import { StatusBar } from '@ionic-native/status-bar/ngx';

import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';
import { AshTimePipe } from './pipes/ash-time.pipe';
import { CustomDatePipe } from './pipes/custom-date.pipe';
import { CustomDatetimePipe } from './pipes/custom-datetime.pipe';

import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HttpClientModule }   from '@angular/common/http';

import { MyCommonServices }   from './shared/myCommonServices';

import { IonicStorageModule } from '@ionic/storage';
import { AuthGuardService } from './services/auth-guard.service';

import { HTTP } from '@ionic-native/http/ngx';
import { NgCalendarModule  } from 'ionic2-calendar';
import { ChartsModule } from "ng2-charts";
import { Geolocation } from '@ionic-native/geolocation/ngx';
import { NativeGeocoder } from '@ionic-native/native-geocoder/ngx';

import { Camera } from '@ionic-native/camera/ngx';
import { FileTransfer, FileTransferObject } from '@ionic-native/file-transfer/ngx';


@NgModule({
  declarations: [AppComponent, AshTimePipe, CustomDatePipe, CustomDatetimePipe],
  entryComponents: [],
  imports: [
    BrowserModule, 
    IonicModule.forRoot({
      mode: "ios",
      scrollAssist: false,
      scrollPadding: false
    }), 
    AppRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    HttpClientModule,
    NgCalendarModule,
    ChartsModule,
    IonicStorageModule.forRoot()
  ],
  providers: [
    StatusBar,
    SplashScreen,
    MyCommonServices,
    AuthGuardService,
    Geolocation,
    NativeGeocoder,
    HTTP,
    FileTransfer,
    FileTransferObject,
    Camera,
    { provide: RouteReuseStrategy, useClass: IonicRouteStrategy }
  ],
  bootstrap: [AppComponent]
})
export class AppModule {}
