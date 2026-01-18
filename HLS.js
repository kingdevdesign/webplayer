function criarHlsConfig(opções = {}) {
  const base = {
    enableWorker: true,
    lowLatencyMode: false,
    enableCEA708Captions: true,
    // Aumenta a tolerância para fragmentos que ainda estão sendo gerados
    manifestLoadingMaxRetry: 50,
    manifestLoadingRetryDelay: 500,
    levelLoadingMaxRetry: 50,
    levelLoadingRetryDelay: 500,
    fragLoadingMaxRetry: 50,
    fragLoadingRetryDelay: 500,
    // Evita erro de buffer vazio em transcodificação lenta
    maxBufferLength: 30,
    maxMaxBufferLength: 60,
  };
  
  if (opções.forSeek) {
    return {
      ...base,
      liveSyncDurationCount: 0,
      startPosition: 0,
    };
  }
  return {
    ...base,
    liveSyncDurationCount: options.isVod ? 0 : 3,
    startPosition: options.isVod ? 0 : -1,
  };
}
