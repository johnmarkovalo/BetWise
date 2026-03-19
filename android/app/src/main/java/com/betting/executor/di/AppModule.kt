package com.betting.executor.di

import com.betting.executor.data.remote.TimeSyncApi
import com.betting.executor.domain.executor.TimeSyncManager
import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import okhttp3.OkHttpClient
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object AppModule {

    @Provides
    @Singleton
    fun provideMoshi(): Moshi {
        return Moshi.Builder()
            .add(KotlinJsonAdapterFactory())
            .build()
    }

    @Provides
    @Singleton
    fun provideOkHttpClient(): OkHttpClient {
        return OkHttpClient.Builder()
            .connectTimeout(10, TimeUnit.SECONDS)
            .readTimeout(10, TimeUnit.SECONDS)
            .writeTimeout(10, TimeUnit.SECONDS)
            .build()
    }

    @Provides
    @Singleton
    fun provideTimeSyncApi(httpClient: OkHttpClient, moshi: Moshi): TimeSyncApi {
        return TimeSyncApi(httpClient, moshi)
    }

    @Provides
    @Singleton
    fun provideTimeSyncManager(timeSyncApi: TimeSyncApi): TimeSyncManager {
        return TimeSyncManager(timeSyncApi)
    }
}
